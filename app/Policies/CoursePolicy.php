<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen', 'mahasiswa'], true);
    }

    public function view(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id)
            || ($user->role === 'mahasiswa' && $user->courses()->whereKey($course->getKey())->exists());
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && (int) $course->lecturer_id === (int) $user->id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && (int) $course->lecturer_id === (int) $user->id);
    }

    public function manageEnrollment(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }
}
