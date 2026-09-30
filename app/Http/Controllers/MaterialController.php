<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorizeCourseRead($request->user(), $course);

        $materials = $course->materials()->with('uploader')->latest()->paginate(15);

        return view('materials.index', compact('course', 'materials'));
    }

    public function show(Request $request, Material $material)
    {
        $this->authorizeCourseRead($request->user(), $material->course);
        $material->load('course', 'uploader');

        return view('materials.show', compact('material'));
    }

    public function showScoped(Request $request, Course $course, Material $material)
    {
        abort_unless($material->course_id === $course->id, 404);

        return $this->show($request, $material);
    }

    private function authorizeCourseRead(User $user, Course $course): void
    {
        $allowed = match ($user->role) {
            'admin' => true,
            'dosen' => $course->lecturer_id === $user->id,
            'mahasiswa' => $user->courses()->whereKey($course->getKey())->exists(),
            default => false,
        };

        abort_unless($allowed, 403);
    }
}
