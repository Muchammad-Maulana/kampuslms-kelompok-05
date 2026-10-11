<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enroll_and_remove_students_from_a_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $firstStudent = User::factory()->create(['role' => 'mahasiswa']);
        $secondStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($firstStudent);

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.enrollment.edit', $course))
            ->assertOk()
            ->assertSee($firstStudent->email)
            ->assertSee($secondStudent->email);

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.enrollment.update', $course), [
                'student_ids' => [$secondStudent->id],
            ])
            ->assertRedirect(route('admin.mata-kuliah.show', $course));

        $this->assertFalse($course->students()->whereKey($firstStudent->id)->exists());
        $this->assertTrue($course->students()->whereKey($secondStudent->id)->exists());

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.enrollment.update', $course), [])
            ->assertRedirect(route('admin.mata-kuliah.show', $course));

        $this->assertCount(0, $course->students()->get());
    }

    public function test_course_lecturer_can_manage_enrollment_but_other_roles_cannot(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->put(route('dosen.mata-kuliah.enrollment.update', $course), [
                'student_ids' => [$student->id],
            ])
            ->assertRedirect(route('dosen.mata-kuliah.show', $course));

        $this->assertTrue($course->students()->whereKey($student->id)->exists());

        $this->actingAs($otherLecturer)
            ->get(route('dosen.mata-kuliah.enrollment.edit', $course))
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('dosen.mata-kuliah.enrollment.update', $course), [])
            ->assertForbidden();
    }

    public function test_enrollment_rejects_non_students_and_duplicate_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.enrollment.update', $course), [
                'student_ids' => [$lecturer->id],
            ])
            ->assertSessionHasErrors('student_ids.0');

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.enrollment.update', $course), [
                'student_ids' => [$student->id, $student->id],
            ])
            ->assertSessionHasErrors('student_ids.1');

        $this->assertCount(0, $course->students()->get());
    }
}