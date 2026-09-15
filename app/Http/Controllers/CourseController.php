<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Menampilkan daftar seluruh mata kuliah.
    public function index()
    {
        $courses = Course::with('lecturer')->get();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = \App\Models\User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        Course::create($validated);

        return redirect()->route('mata-kuliah.index');
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
    
    public function update(Request $request, $mataKuliah)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code,' . $mataKuliah,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        $course = Course::findOrFail($mataKuliah);

        $course->update($validated);

        return redirect()->route('mata-kuliah.show', $mataKuliah);
    }

    public function destroy($mataKuliah)
    {
        $course = Course::findOrFail($mataKuliah);

        $course->delete();

        return redirect()->route('mata-kuliah.index');
    }
}
