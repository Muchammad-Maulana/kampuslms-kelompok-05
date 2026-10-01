<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    // Placeholder responses keep the resource routes callable until view and persistence logic are added.
    public function index(Course $course): string
    {
        $this->assertCanReadCourse($course);

        return "Daftar materi untuk mata kuliah {$course->id}.";
    }

    public function create(Course $course): string
    {
        $this->assertCanManageCourse($course);

        return "Form materi untuk mata kuliah {$course->id}.";
    }

    public function store(Request $request, Course $course): string
    {
        $this->assertCanManageCourse($course);

        return "Penyimpanan materi untuk mata kuliah {$course->id} belum diimplementasikan.";
    }

    public function show(Material $material): string
    {
        $this->assertCanReadCourse($material->course);

        return "Detail materi {$material->id}.";
    }

    public function showInCourse(Course $course, Material $material): string
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        $this->assertCanReadCourse($course);

        return "Materi {$material->id} pada mata kuliah {$course->id}.";
    }

    public function edit(Material $material): string
    {
        $this->assertCanManageCourse($material->course);

        return "Form edit materi {$material->id}.";
    }

    public function update(Request $request, Material $material): string
    {
        $this->assertCanManageCourse($material->course);

        return "Pembaruan materi {$material->id} belum diimplementasikan.";
    }

    public function destroy(Material $material): string
    {
        $this->assertCanManageCourse($material->course);

        return "Penghapusan materi {$material->id} belum diimplementasikan.";
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
