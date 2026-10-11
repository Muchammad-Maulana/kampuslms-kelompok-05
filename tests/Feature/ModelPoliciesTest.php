<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_scopes_filter_courses_materials_and_assignments_in_the_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $course->students()->attach($student);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Terdaftar',
            'type' => 'link',
            'external_url' => 'https://example.test/registered',
        ]);
        $otherMaterial = Material::create([
            'course_id' => $otherCourse->id,
            'uploaded_by' => $otherLecturer->id,
            'title' => 'Materi Course Lain',
            'type' => 'link',
            'external_url' => 'https://example.test/other',
        ]);
        $published = $this->createAssignment($course, $lecturer);
        $draft = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Draft',
            'instructions' => 'Belum dipublikasikan',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'draft',
        ]);
        $otherAssignment = $this->createAssignment($otherCourse, $otherLecturer);

        $this->assertSame(
            [$course->id],
            Course::query()->visibleTo($lecturer)->pluck('id')->all()
        );
        $this->assertSame(
            [$course->id],
            Course::query()->visibleTo($student)->pluck('id')->all()
        );
        $this->assertCount(2, Course::query()->visibleTo($admin)->get());

        $this->assertSame(
            [$material->id],
            Material::query()->visibleTo($student)->pluck('id')->all()
        );
        $this->assertSame(
            [$material->id],
            Material::query()->visibleTo($lecturer)->pluck('id')->all()
        );
        $this->assertCount(2, Material::query()->visibleTo($admin)->get());

        $this->assertSame(
            [$published->id],
            Assignment::query()->visibleTo($student)->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$published->id, $draft->id],
            Assignment::query()->visibleTo($lecturer)->pluck('id')->all()
        );
        $this->assertCount(3, Assignment::query()->visibleTo($admin)->get());
        $this->assertNotContains($otherMaterial->id, Material::query()->visibleTo($student)->pluck('id')->all());
        $this->assertNotContains($otherAssignment->id, Assignment::query()->visibleTo($student)->pluck('id')->all());
    }

    public function test_course_material_assignment_submission_and_grade_policies_enforce_ownership(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);

        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach([$student->id, $otherStudent->id]);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Policy',
            'type' => 'link',
            'external_url' => 'https://example.test/material',
        ]);
        $assignment = $this->createAssignment($course, $lecturer);
        $studentSubmission = $this->createSubmission($assignment, $student);
        $otherSubmission = $this->createSubmission($assignment, $otherStudent);
        $grade = Grade::create([
            'submission_id' => $studentSubmission->id,
            'score' => 85,
            'feedback' => 'Baik',
            'graded_by' => $lecturer->id,
            'graded_at' => now(),
        ]);

        $this->assertTrue($admin->can('create', Course::class));
        $this->assertTrue($lecturer->can('view', $course));
        $this->assertFalse($otherLecturer->can('view', $course));

        $this->assertTrue($student->can('view', $material));
        $this->assertTrue($lecturer->can('update', $material));
        $this->assertFalse($otherLecturer->can('update', $material));

        $this->assertTrue($lecturer->can('create', [Assignment::class, $course]));
        $this->assertTrue($student->can('view', $assignment));
        $this->assertFalse($otherLecturer->can('view', $assignment));

        $this->assertTrue($student->can('create', [Submission::class, $assignment]));
        $this->assertTrue($student->can('view', $studentSubmission));
        $this->assertFalse($student->can('view', $otherSubmission));
        $this->assertFalse($student->can('viewAny', [Submission::class, $assignment]));

        $this->assertTrue($student->can('view', $grade));
        $this->assertFalse($otherStudent->can('view', $grade));
        $this->assertTrue($lecturer->can('update', $grade));
        $this->assertFalse($otherLecturer->can('update', $grade));
    }

    private function createAssignment(Course $course, User $lecturer): Assignment
    {
        return Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Policy',
            'instructions' => 'Instruksi tugas',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => false,
            'status' => 'published',
        ]);
    }

    private function createSubmission(Assignment $assignment, User $student): Submission
    {
        return Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/policy.pdf',
            'original_name' => 'policy.pdf',
            'file_size' => 1000,
            'submitted_at' => now(),
            'is_late' => false,
        ]);
    }
}
