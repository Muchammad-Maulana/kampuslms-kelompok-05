<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    // Menampilkan daftar seluruh mata kuliah.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $allowedStatuses = ['draft', 'active', 'archived'];
        $status = $request->query('status', '');
        $status = in_array($status, $allowedStatuses, true) ? $status : '';

        $courses = Course::with('lecturer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses', 'search', 'status'));
    }

    public function create()
    {
        $lecturers = \App\Models\User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        Course::create($validated);

        return redirect()
            ->route('dosen.mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function apiStore(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    public function show(Course $course)
    {
        $course->load('lecturer');

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        Gate::authorize('manage', $course);

        $course->load('lecturer');

        $lecturers = \App\Models\User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }
    
    public function update(UpdateCourseRequest $request, Course $course)
    {
        Gate::authorize('manage', $course);

        $validated = $request->validated();

        $course->update($validated);

        return redirect()
            ->route('dosen.mata-kuliah.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('manage', $course);

        $course->delete();

        return redirect()
            ->route('dosen.mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
