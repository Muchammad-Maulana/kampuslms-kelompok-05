<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GradeAuthorizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_lecturer_and_admin_cannot_grade_submission(): void
    {
        [$lecturer, , $course, $assignment, $submission] = $this->createGradeContext();
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($otherLecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]), [
                'score' => 80,
                'feedback' => 'Tidak berhak menilai.',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]), [
                'score' => 80,
                'feedback' => 'Admin tidak menilai.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    public function test_grade_validation_rejects_scores_outside_max_score_and_long_feedback(): void
    {
        [$lecturer, , $course, $assignment, $submission] = $this->createGradeContext(['max_score' => 50]);
        $url = route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]);

        $this->actingAs($lecturer)
            ->post($url, [
                'score' => 51,
                'feedback' => 'Di atas nilai maksimal.',
            ])
            ->assertSessionHasErrors('score');

        $this->actingAs($lecturer)
            ->post($url, [
                'score' => 50,
                'feedback' => str_repeat('a', 2001),
            ])
            ->assertSessionHasErrors('feedback');

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    public function test_grade_route_rejects_mismatched_parent_ids(): void
    {
        [$lecturer, , $course, $assignment, $submission] = $this->createGradeContext();
        $otherCourse = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherAssignment = $this->createAssignment($otherCourse, $lecturer);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$otherCourse, $otherAssignment, $submission]), [
                'score' => 80,
            ])
            ->assertNotFound();

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]), [
                'score' => 80,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 80,
        ]);
    }

    public function test_lecturer_can_regrade_submission_without_creating_duplicate_grade(): void
    {
        Queue::fake();

        [$lecturer, , $course, $assignment, $submission] = $this->createGradeContext();
        $url = route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]);

        $this->actingAs($lecturer)
            ->post($url, [
                'score' => 70,
                'feedback' => 'Nilai pertama.',
            ])
            ->assertRedirect();

        $this->actingAs($lecturer)
            ->post($url, [
                'score' => 90,
                'feedback' => 'Nilai setelah revisi.',
            ])
            ->assertRedirect();

        $this->assertSame(1, Grade::query()->where('submission_id', $submission->id)->count());
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 90,
            'feedback' => 'Nilai setelah revisi.',
            'graded_by' => $lecturer->id,
        ]);
    }

    private function createGradeContext(array $assignmentOverrides = []): array
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);
        $course->students()->attach($student);
        $assignment = $this->createAssignment($course, $lecturer, $assignmentOverrides);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/'.$assignment->id.'/grade.pdf',
            'original_name' => 'grade.pdf',
            'file_size' => 100,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        return [$lecturer, $student, $course, $assignment, $submission];
    }

    private function createAssignment(Course $course, User $lecturer, array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Nilai',
            'instructions' => 'Kumpulkan jawaban.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ], $overrides));
    }
}
