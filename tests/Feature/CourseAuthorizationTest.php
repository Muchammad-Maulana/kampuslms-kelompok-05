<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_cannot_manage_another_dosens_course(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::create([
            'code' => 'SI100001',
            'name' => 'Pemrograman Web',
            'sks' => 3,
            'lecturer_id' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($otherLecturer)
            ->get(route('dosen.mata-kuliah.edit', $course))
            ->assertForbidden()
            ->assertSee('Akses ditolak');

        $this->actingAs($otherLecturer)
            ->put(route('dosen.mata-kuliah.update', $course), [
                'code' => $course->code,
                'name' => 'Nama hasil percobaan',
                'sks' => $course->sks,
                'lecturer_id' => $owner->id,
                'status' => $course->status,
            ])
            ->assertForbidden();

        $this->actingAs($otherLecturer)
            ->delete(route('dosen.mata-kuliah.destroy', $course))
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => $course->name,
            'lecturer_id' => $owner->id,
        ]);
    }

    public function test_course_owner_can_open_the_edit_page(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::create([
            'code' => 'SI100002',
            'name' => 'Basis Data',
            'sks' => 3,
            'lecturer_id' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get(route('dosen.mata-kuliah.edit', $course))
            ->assertOk();
    }
}