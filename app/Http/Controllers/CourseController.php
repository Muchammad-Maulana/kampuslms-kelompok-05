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
        $this->authorize('viewAny', Course::class);

        $user = $request->user();
        $query = Course::query()->visibleTo($user);
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
        return view('courses.index', compact('courses', 'routePrefix', 'totalSks'));
    }

    public function create()
    {
        $this->authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        $this->authorize('create', Course::class);

        $validated = $request->validated();

        Course::create($validated);

        return redirect()->route('admin.mata-kuliah.index')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function show(Request $request, Course $course)
    {
        $this->authorize('view', $course);

        $user = $request->user();

        $course->load('lecturer', 'materials');
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
        return view('courses.show', compact('course', 'routePrefix'));
    }

    public function edit(Course $course)
    {
        $this->authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validated();

        $course->update($validated);

        return redirect()->route('admin.mata-kuliah.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        $course->materials()->get()->each->delete();
        $course->assignments()->with('submissions')->get()->each(function ($assignment): void {
            $assignment->submissions->each->delete();
            $assignment->delete();
        });
        $course->delete();

        return redirect()->route('admin.mata-kuliah.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
