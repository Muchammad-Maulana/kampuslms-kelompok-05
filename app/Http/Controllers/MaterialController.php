<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()
            ->visibleTo($request->user())
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return view('materials.index', compact('course', 'materials'));
    }

    public function show(Request $request, Material $material)
    {
        $this->authorize('view', $material);
        $material->load('course', 'uploader');

        return view('materials.show', compact('material'));
    }

    public function showScoped(Request $request, Course $course, Material $material)
    {
        abort_unless($material->course_id === $course->id, 404);

        return $this->show($request, $material);
    }
}
