<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class GradeReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public Grade $grade) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->grade->loadMissing('submission.assignment.course');
        $assignment = $this->grade->submission->assignment;
        $course = $assignment->course;

        return [
            'kind' => 'grade_released',
            'submission_id' => $this->grade->submission->getKey(),
            'assignment_id' => $assignment->getKey(),
            'assignment_title' => $assignment->title,
            'course_id' => $course->getKey(),
            'course_name' => $course->name,
            'url' => route('mahasiswa.mata-kuliah.assignments.scoped-show', [
                'course' => $course,
                'assignment' => $assignment,
            ]),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}
