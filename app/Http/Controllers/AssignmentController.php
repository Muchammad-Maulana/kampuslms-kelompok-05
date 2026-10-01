<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    // Placeholder responses keep the resource routes callable until view and persistence logic are added.
    public function index(Course $course): string
    {
        $this->assertCanReadCourse($course);

        return "Daftar tugas untuk mata kuliah {$course->id}.";
    }

    public function create(Course $course): string
    {
        $this->assertCanManageCourse($course);

        return "Form tugas untuk mata kuliah {$course->id}.";
    }

    public function store(Request $request, Course $course): string
    {
        $this->assertCanManageCourse($course);

        return "Penyimpanan tugas untuk mata kuliah {$course->id} belum diimplementasikan.";
    }

    public function show(Assignment $assignment): string
    {
        $this->assertCanReadCourse($assignment->course);

        return "Detail tugas {$assignment->id}.";
    }

    public function showInCourse(Course $course, Assignment $assignment): string
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        $this->assertCanReadCourse($course);

        return "Tugas {$assignment->id} pada mata kuliah {$course->id}.";
    }

    public function edit(Assignment $assignment): string
    {
        $this->assertCanManageCourse($assignment->course);

        return "Form edit tugas {$assignment->id}.";
    }

    public function update(Request $request, Assignment $assignment): string
    {
        $this->assertCanManageCourse($assignment->course);

        return "Pembaruan tugas {$assignment->id} belum diimplementasikan.";
    }

    public function destroy(Assignment $assignment): string
    {
        $this->assertCanManageCourse($assignment->course);

        return "Penghapusan tugas {$assignment->id} belum diimplementasikan.";
    }

    // Temporary ownership checks; move these rules into policies in week 7.
    private function assertCanReadCourse(Course $course): void
    {
        $user = auth()->user();

        abort_unless($user && (
            $user->role === 'admin'
            || ($user->role === 'dosen' && (int) $course->lecturer_id === (int) $user->id)
            || ($user->role === 'student' && $course->students()->where('users.id', $user->id)->exists())
        ), 403);
    }

    private function assertCanManageCourse(Course $course): void
    {
        $user = auth()->user();

        abort_unless($user && (
            $user->role === 'admin'
            || ($user->role === 'dosen' && (int) $course->lecturer_id === (int) $user->id)
        ), 403);
    }
}
