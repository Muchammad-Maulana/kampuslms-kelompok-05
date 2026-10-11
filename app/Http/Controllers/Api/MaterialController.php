<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request, Course $course): object
    {
        $this->authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()
            ->visibleTo($request->user())
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return MaterialResource::collection($materials);
    }
}