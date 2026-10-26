<?php

namespace App\Http\Controllers;

use App\Events\AssignmentPublished;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function create(Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);
        $assignment = new Assignment;

        return view('assignments.form', compact('course', 'assignment'));
    }

    public function store(StoreAssignmentRequest $request, Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        $assignment = Assignment::create(array_merge($request->validated(), [
            'course_id' => $course->id,
            'created_by' => $request->user()->id,
        ]));

        if ($assignment->status === 'published') {
            AssignmentPublished::dispatch($assignment);
        }

        return redirect()->route($request->user()->role.'.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function edit(Assignment $assignment)
    {
        Gate::authorize('update', $assignment);
        $course = $assignment->course;

        return view('assignments.form', compact('course', 'assignment'));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);
        $wasPublished = $assignment->status === 'published';
        $assignment->update($request->validated());

        if (! $wasPublished && $assignment->status === 'published') {
            AssignmentPublished::dispatch($assignment);
        }

        return redirect()->route($request->user()->role.'.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        Gate::authorize('delete', $assignment);
        $course = $assignment->course;
        $assignment->submissions()->get()->each->delete();
        $assignment->delete();

        return redirect()->route($request->user()->role.'.mata-kuliah.assignments.index', $course)
            ->with('success', 'Tugas berhasil dihapus.');
    }

    public function show(Request $request, Assignment $assignment)
    {
        abort_if($request->user()->role === 'mahasiswa' && $assignment->status !== 'published', 404);
        $this->authorize('view', $assignment);
        $assignment->load('course');

        if ($request->user()->role === 'mahasiswa') {
            $studentSubmission = $assignment->submissions()
                ->with('grade')
                ->where('user_id', $request->user()->id)
                ->first();
            $canSubmit = $request->user()->can('create', [Submission::class, $assignment]);
            $students = collect();
            $submissions = collect();
        } else {
            $assignment->course->load('students');
            $students = $assignment->course->students->keyBy('id');
            $submissions = $assignment->submissions()
                ->with(['user', 'grade'])
                ->get()
                ->keyBy('user_id');
            $studentSubmission = null;
            $canSubmit = false;
        }

        return view('assignments.show', compact('assignment', 'studentSubmission', 'students', 'submissions', 'canSubmit'));
    }

    public function showScoped(Request $request, Course $course, Assignment $assignment)
    {
        abort_unless($assignment->course_id === $course->id, 404);

        return $this->show($request, $assignment);
    }
}
