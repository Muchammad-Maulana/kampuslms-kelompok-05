<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyStudentsOfPublishedAssignment implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->loadMissing('course');
        $students = $assignment->course->students()
            ->where('users.role', 'mahasiswa')
            ->get(['users.id']);

        Notification::send($students, new NewAssignmentNotification($assignment));
    }

    public function failed(AssignmentPublished $event, Throwable $exception): void
    {
        Log::error('Failed to queue assignment publication notifications.', [
            'assignment_id' => $event->assignment->getKey(),
            'exception' => $exception->getMessage(),
        ]);
    }
}
