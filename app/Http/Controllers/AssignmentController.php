<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorizeCourseRead($request->user(), $course);

        $assignments = $course->assignments()
            ->when($request->user()->role === 'mahasiswa', fn ($query) => $query->where('status', 'published'))
            ->with('creator')
            ->latest()
            ->paginate(15);

        return view('assignments.index', compact('course', 'assignments'));
    }

    public function show(Request $request, Assignment $assignment)
    {
        $this->authorizeCourseRead($request->user(), $assignment->course);
        abort_if($request->user()->role === 'mahasiswa' && $assignment->status !== 'published', 404);
        $assignment->load('course', 'creator');

        return view('assignments.show', compact('assignment'));
    }

    public function showScoped(Request $request, Course $course, Assignment $assignment)
    {
        abort_unless($assignment->course_id === $course->id, 404);

        return $this->show($request, $assignment);
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
