<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCourseEnrollmentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function edit(Course $course): View
    {
        $this->authorize('manageEnrollment', $course);

        $students = User::query()
            ->where('role', 'mahasiswa')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nim_nip']);
        $enrolledStudentIds = $course->students()->pluck('users.id')->all();

        return view('courses.enrollment', compact('course', 'students', 'enrolledStudentIds'));
    }

    public function update(UpdateCourseEnrollmentRequest $request, Course $course): RedirectResponse
    {
        $course->students()->sync($request->validated()['student_ids'] ?? []);

        return redirect()->route($request->user()->role . '.mata-kuliah.show', $course)
            ->with('success', 'Pendaftaran mahasiswa berhasil diperbarui.');
    }
}