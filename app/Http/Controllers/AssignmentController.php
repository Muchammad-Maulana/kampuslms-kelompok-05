<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorize('viewAny', [Assignment::class, $course]);

        $assignments = $course->assignments()
            ->visibleTo($request->user())
            ->with('creator')
            ->latest()
            ->paginate(15);

        return view('assignments.index', compact('course', 'assignments'));
    }

    public function show(Request $request, Assignment $assignment)
    {
        abort_if($request->user()->role === 'mahasiswa' && $assignment->status !== 'published', 404);
        $this->authorize('view', $assignment);
        $assignment->load('course', 'creator');

        return view('assignments.show', compact('assignment'));
    }

    public function showScoped(Request $request, Course $course, Assignment $assignment)
    {
        abort_unless($assignment->course_id === $course->id, 404);

        return $this->show($request, $assignment);
    }
}
