<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = match ($user->role) {
            'admin' => Course::query(),
            'dosen' => $user->taughtCourses(),
            'mahasiswa' => $user->courses(),
            default => abort(403),
        };
        $query->with('lecturer');

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));

            $query->where(function ($courseQuery) use ($keyword) {
                $courseQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $totalSks = (clone $query)->sum('courses.sks');
        $courses = $query->latest('courses.created_at')->paginate(10)->withQueryString();
        $routePrefix = match ($user->role) {
            'admin' => 'admin.mata-kuliah',
            'dosen' => 'dosen.mata-kuliah',
            'mahasiswa' => 'mahasiswa.mata-kuliah',
        };
        $canManage = $user->role === 'admin';

        return view('courses.index', compact('courses', 'routePrefix', 'canManage', 'totalSks'));
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        Course::create($validated);

        return redirect()->route('admin.mata-kuliah.index')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $canView = match ($user->role) {
            'admin' => true,
            'dosen' => $course->lecturer_id === $user->id,
            'mahasiswa' => $user->courses()->whereKey($course->getKey())->exists(),
            default => false,
        };

        abort_unless($canView, 403);

        $course->load('lecturer');
        $course->load(['assignments' => function ($query) use ($user): void {
            if ($user->role === 'mahasiswa') {
                $query->where('status', 'published');
            }
        }]);
        $routePrefix = match ($user->role) {
            'admin' => 'admin.mata-kuliah',
            'dosen' => 'dosen.mata-kuliah',
            'mahasiswa' => 'mahasiswa.mata-kuliah',
        };
        $canManage = $user->role === 'admin';

        return view('courses.show', compact('course', 'routePrefix', 'canManage'));
    }

    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        $course->update($validated);

        return redirect()->route('admin.mata-kuliah.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('admin.mata-kuliah.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
