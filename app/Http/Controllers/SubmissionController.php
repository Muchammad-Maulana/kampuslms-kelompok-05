<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id, 404);
        Gate::authorize('create', [Submission::class, $assignment]);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = $assignment->submissions()->where('user_id', $request->user()->id)->first();
        $file = $validated['file'];
        $path = $file->store('submissions/'.$assignment->id, 'local');
        $oldPath = $existing?->file_path;
        $submittedAt = now();

        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'note' => $validated['note'] ?? null,
                'submitted_at' => $submittedAt,
                'is_late' => $submittedAt->greaterThan($assignment->due_at),
            ]
        );

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment])
            ->with('success', 'Tugas berhasil dikirim.');
    }

    public function update(Request $request, Course $course, Assignment $assignment, Submission $submission): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);
        $this->authorize('update', $submission);

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->hasFile('file')) {
            $file = $validated['file'];
            $oldPath = $submission->file_path;
            $submittedAt = now();
            $submission->file_path = $file->store('submissions/'.$assignment->id, 'local');
            $submission->original_name = $file->getClientOriginalName();
            $submission->file_size = $file->getSize();
            $submission->submitted_at = $submittedAt;
            $submission->is_late = $submittedAt->greaterThan($assignment->due_at);
        } else {
            $oldPath = null;
        }

        $submission->note = $validated['note'] ?? $submission->note;
        $submission->save();

        if ($oldPath && $oldPath !== $submission->file_path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment])
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function download(Submission $submission): StreamedResponse
    {
        Gate::authorize('view', $submission);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }

    public function grade(Request $request, Course $course, Assignment $assignment, Submission $submission): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);
        $this->authorize('create', [\App\Models\Grade::class, $submission]);

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ]
        );

        return redirect()->route(auth()->user()->role.'.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment])
            ->with('success', 'Nilai tugas berhasil disimpan.');
    }
}
