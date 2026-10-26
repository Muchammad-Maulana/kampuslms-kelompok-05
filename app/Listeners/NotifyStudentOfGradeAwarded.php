<?php

namespace App\Listeners;

use App\Events\GradeAwarded;
use App\Notifications\GradeReleasedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyStudentOfGradeAwarded implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    public function handle(GradeAwarded $event): void
    {
        $grade = $event->grade->loadMissing('submission.user', 'submission.assignment.course');
        $student = $grade->submission->user;

        Notification::send($student, new GradeReleasedNotification($grade));
    }

    public function failed(GradeAwarded $event, Throwable $exception): void
    {
        Log::error('Failed to queue grade notification.', [
            'grade_id' => $event->grade->getKey(),
            'exception' => $exception->getMessage(),
        ]);
    }
}
