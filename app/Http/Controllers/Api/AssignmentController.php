<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course): object
    {
        $this->authorizeCourseRead($request->user(), $course);

        $query = $course->assignments()->with('creator');

        if ($request->user()->role === 'mahasiswa') {
            $query->where('status', 'published');
        }

        $assignments = $query->latest('due_at')->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseManage($request->user(), $course);

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
        $this->authorizeCourseRead($request->user(), $course);

        if ($request->user()->role === 'mahasiswa' && $assignment->status !== 'published') {
            abort(404);
        }

        return new AssignmentResource($assignment->load('creator'));
    }

    public function update(Request $request, Course $course, Assignment $assignment): AssignmentResource
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $this->authorizeCourseManage($request->user(), $course);

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
        $this->authorizeCourseManage($request->user(), $course);

        $assignment->delete();

        return response()->noContent();
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

    private function authorizeCourseManage(User $user, Course $course): void
    {
        $allowed = $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);

        abort_unless($allowed, 403);
    }
}
