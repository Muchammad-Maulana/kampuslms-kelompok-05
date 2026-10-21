<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user, Assignment $assignment): bool
    {
        return $this->canManageCourse($user, $assignment);
    }

    public function view(User $user, Submission $submission): bool
    {
        return $this->canManageCourse($user, $submission->assignment)
            || ($user->role === 'mahasiswa' && $submission->user_id === $user->id);
    }

    public function create(User $user, Assignment $assignment): bool
    {
        return $user->role === 'mahasiswa'
            && $assignment->course->status === 'active'
            && $assignment->status === 'published'
            && $user->courses()->whereKey($assignment->course_id)->exists()
            && ($assignment->allow_late || now()->lessThanOrEqualTo($assignment->due_at));
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->role === 'mahasiswa'
            && $submission->user_id === $user->id
            && $this->create($user, $submission->assignment);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->update($user, $submission);
    }

    private function canManageCourse(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id);
    }
}
