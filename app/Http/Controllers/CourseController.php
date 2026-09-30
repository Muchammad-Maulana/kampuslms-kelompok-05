<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;

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
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function apiStore(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    public function show($mataKuliah)
    {
        $course = Course::with('lecturer')->findOrFail($mataKuliah);

        return view('courses.show', compact('course'));
    }

    public function edit($mataKuliah)
    {
          $course = Course::with('lecturer')->findOrFail($mataKuliah);

        $lecturers = \App\Models\User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }
    
    public function update(UpdateCourseRequest $request, $mataKuliah)
    {
        $validated = $request->validated();

        $course = Course::findOrFail($mataKuliah);

        $course->update($validated);

        return redirect()
            ->route('mata-kuliah.show', $mataKuliah)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy($mataKuliah)
    {
        $course = Course::find($mataKuliah);

        if (!$course) {
            return redirect()
                ->route('mata-kuliah.index')
                ->with('error', 'Mata kuliah tidak ditemukan atau sudah dihapus.');
        }

        $course->delete();

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
