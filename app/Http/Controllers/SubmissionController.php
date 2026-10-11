<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function store(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $this->authorize('create', [\App\Models\Submission::class, $assignment]);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,txt,zip,rar', 'max:20480'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = $assignment->submissions()->where('user_id', $request->user()->id)->first();
        if ($existing) {
            if ($existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
                Storage::disk('local')->delete($existing->file_path);
            }
        }

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $path = $file->storeAs(
            'submissions/'.$assignment->id,
            $request->user()->id.'-'.time().'-'.$file->getClientOriginalName(),
            'local'
        );

        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'note' => $validated['note'] ?? null,
                'submitted_at' => now(),
                'is_late' => now()->greaterThan($assignment->due_at),
            ]
        );

        return redirect()->route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment])
            ->with('success', 'Tugas berhasil dikirim.');
    }

    public function update(Request $request, Course $course, Assignment $assignment, Submission $submission): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);
        $this->authorize('update', $submission);

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,txt,zip,rar', 'max:20480'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->hasFile('file')) {
            /** @var UploadedFile $file */
            $file = $validated['file'];
            if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
                Storage::disk('local')->delete($submission->file_path);
            }
            $submission->file_path = $file->storeAs(
                'submissions/'.$assignment->id,
                $request->user()->id.'-'.time().'-'.$file->getClientOriginalName(),
                'local'
            );
            $submission->original_name = $file->getClientOriginalName();
            $submission->file_size = $file->getSize();
            $submission->submitted_at = now();
            $submission->is_late = now()->greaterThan($assignment->due_at);
        }

        $submission->note = $validated['note'] ?? $submission->note;
        $submission->save();

        return redirect()->route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment])
            ->with('success', 'Tugas berhasil diperbarui.');
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
