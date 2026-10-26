<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user, Course $course): bool
    {
        return $user->can('view', $course);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($this->canManageCourse($user, $assignment->course)) {
            return true;
        }

        return $user->role === 'mahasiswa'
            && $assignment->course->status === 'active'
            && $assignment->status === 'published'
            && $user->courses()->whereKey($assignment->course_id)->exists();
    }

    public function create(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->canManageCourse($user, $assignment->course);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->canManageCourse($user, $assignment->course);
    }

    private function canManageCourse(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }
}
