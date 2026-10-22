<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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

    public function create(Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);
        $material = new Material();

        return view('materials.form', compact('course', 'material'));
    }

    public function store(StoreMaterialRequest $request, Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validated();
        $file = $validated['file'] ?? null;
        unset($validated['file']);

        if ($file) {
            $path = $file->store('materials/'.$course->id, 'local');
            abort_unless($path, 500);

            $validated = array_merge($validated, [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        $material = Material::create(array_merge($validated, [
            'course_id' => $course->id,
            'uploaded_by' => $request->user()->id,
        ]));

        return redirect()->route($request->user()->role . '.materials.show', $material)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    public function edit(Material $material)
    {
        Gate::authorize('update', $material);
        $course = $material->course;

        return view('materials.form', compact('course', 'material'));
    }

    public function update(UpdateMaterialRequest $request, Material $material)
    {
        Gate::authorize('update', $material);
        $validated = $request->validated();
        $file = $validated['file'] ?? null;
        unset($validated['file']);

        if ($file) {
            $path = $file->store('materials/'.$material->course_id, 'local');
            abort_unless($path, 500);

            $validated = array_merge($validated, [
                'type' => 'file',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'external_url' => null,
            ]);
        }

        $material->update($validated);

        return redirect()->route($request->user()->role . '.materials.show', $material)
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Request $request, Material $material)
    {
        Gate::authorize('delete', $material);
        $course = $material->course;
        $material->delete();

        return redirect()->route($request->user()->role . '.mata-kuliah.materials.index', $course)
            ->with('success', 'Materi berhasil dihapus.');
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

    public function download(Course $course, Material $material)
    {
        abort_unless($material->course_id === $course->id, 404);
        $this->authorize('download', $material);
        abort_unless($material->type === 'file' && $material->file_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($material->file_path), 404);

        return $disk->download($material->file_path, $material->original_name);
    }
}
