<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function viewAny(User $user, Course $course): bool
    {
        return $user->can('view', $course);
    }

    public function view(User $user, Material $material): bool
    {
        return $user->can('view', $material->course);
    }

    public function download(User $user, Material $material): bool
    {
        return $user->role === 'mahasiswa'
            && $user->courses()->whereKey($material->course_id)->exists();
    }

    public function create(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function update(User $user, Material $material): bool
    {
        return $this->canManageCourse($user, $material->course);
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->canManageCourse($user, $material->course);
    }

    private function canManageCourse(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }
}
