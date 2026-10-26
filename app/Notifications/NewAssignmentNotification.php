<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public Assignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->assignment->loadMissing('course');

        return [
            'kind' => 'assignment_published',
            'assignment_id' => $this->assignment->getKey(),
            'assignment_title' => $this->assignment->title,
            'course_id' => $this->assignment->course->getKey(),
            'course_name' => $this->assignment->course->name,
            'url' => route('mahasiswa.mata-kuliah.assignments.scoped-show', [
                'course' => $this->assignment->course,
                'assignment' => $this->assignment,
            ]),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}
