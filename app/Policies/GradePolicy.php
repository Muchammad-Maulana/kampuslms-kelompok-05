<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user, Submission $submission): bool
    {
        return $this->canViewSubmission($user, $submission);
    }

    public function view(User $user, Grade $grade): bool
    {
        return $this->canViewSubmission($user, $grade->submission);
    }

    public function create(User $user, Submission $submission): bool
    {
        return $this->canManageCourse($user, $submission);
    }

    public function update(User $user, Grade $grade): bool
    {
        return $this->canManageCourse($user, $grade->submission);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $this->canManageCourse($user, $grade->submission);
    }

    private function canViewSubmission(User $user, Submission $submission): bool
    {
        return $this->canManageCourse($user, $submission)
            || ($user->role === 'mahasiswa' && $submission->user_id === $user->id);
    }

    private function canManageCourse(User $user, Submission $submission): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen'
                && $submission->assignment->course->lecturer_id === $user->id);
    }
}
