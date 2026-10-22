<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_cannot_open_another_lecturers_course_edit_form(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::query()->create([
            'code' => 'SI000001',
            'name' => 'Basis Data',
            'sks' => 3,
            'lecturer_id' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($otherLecturer)
            ->get(route('dosen.mata-kuliah.edit', $course))
            ->assertForbidden();
    }

    public function test_dosen_cannot_update_or_delete_another_lecturers_course(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::query()->create([
            'code' => 'SI000002',
            'name' => 'Pemrograman Web',
            'sks' => 3,
            'lecturer_id' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($otherLecturer)
            ->put(route('dosen.mata-kuliah.update', $course), [
                'code' => 'SI999999',
                'name' => 'Course hasil IDOR',
                'description' => 'Percobaan perubahan tanpa izin.',
                'sks' => 3,
                'lecturer_id' => $owner->id,
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
        ]);

        $this->delete(route('dosen.mata-kuliah.destroy', $course))
            ->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}