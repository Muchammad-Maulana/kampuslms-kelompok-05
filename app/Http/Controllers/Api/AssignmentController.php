<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course): object
    {
        $this->authorize('viewAny', [Assignment::class, $course]);

        $filters = $request->validate([
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $assignments = $course->assignments()
            ->visibleTo($request->user())
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->with('creator')
            ->latest('due_at')
            ->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorize('create', [Assignment::class, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['sometimes', 'integer', 'between:1,100'],
            'allow_late' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $assignment = $course->assignments()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return AssignmentResource::make($assignment->load('creator'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Course $course, Assignment $assignment): AssignmentResource
    {
        abort_unless($assignment->course_id === $course->id, 404);

        if ($request->user()->role === 'mahasiswa' && $assignment->status !== 'published') {
            abort(404);
        }
        $this->authorize('view', $assignment);

        return new AssignmentResource($assignment->load('creator'));
    }

    public function update(Request $request, Course $course, Assignment $assignment): AssignmentResource
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['sometimes', 'required', 'string'],
            'due_at' => ['sometimes', 'required', 'date'],
            'max_score' => ['sometimes', 'integer', 'between:1,100'],
            'allow_late' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $assignment->update($validated);

        return new AssignmentResource($assignment->load('creator'));
    }

    public function destroy(Request $request, Course $course, Assignment $assignment): \Illuminate\Http\Response
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return response()->noContent();
    }

    public function storeStandalone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['sometimes', 'integer', 'between:1,100'],
            'allow_late' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);
        $course = Course::findOrFail($validated['course_id']);
        $this->authorize('create', [Assignment::class, $course]);

        $assignment = $course->assignments()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return AssignmentResource::make($assignment->load('creator'))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStandalone(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorize('update', $assignment);
        $assignment->update($request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['sometimes', 'required', 'string'],
            'due_at' => ['sometimes', 'required', 'date'],
            'max_score' => ['sometimes', 'integer', 'between:1,100'],
            'allow_late' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published'],
        ]));

        return new AssignmentResource($assignment->load('creator'));
    }

    public function destroyStandalone(Request $request, Assignment $assignment): \Illuminate\Http\Response
    {
        $this->authorize('delete', $assignment);
        $assignment->delete();

        return response()->noContent();
    }
}
