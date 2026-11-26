<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionAuthorizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unenrolled_student_cannot_submit_assignment(): void
    {
        Storage::fake('local');

        [$lecturer, , $course, $assignment] = $this->createAssignmentContext();
        $outsider = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($outsider)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_student_cannot_update_another_students_submission(): void
    {
        [$lecturer, $owner, $course, $assignment] = $this->createAssignmentContext();
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($otherStudent);
        $submission = $this->createSubmission($assignment, $owner);

        $this->actingAs($otherStudent)
            ->put(route('mahasiswa.mata-kuliah.assignments.submissions.update', [$course, $assignment, $submission]), [
                'note' => 'Tidak boleh diubah',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'user_id' => $owner->id,
            'note' => null,
        ]);
    }

    public function test_submission_routes_reject_mismatched_course_assignment_and_submission_pairs(): void
    {
        Storage::fake('local');

        [$lecturer, $student, $course] = $this->createAssignmentContext();
        $otherCourse = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse->students()->attach($student);
        $otherAssignment = $this->createAssignment($otherCourse, $lecturer);
        $submission = $this->createSubmission($otherAssignment, $student);

        $this->actingAs($student)
            ->put(route('mahasiswa.mata-kuliah.assignments.submissions.update', [$course, $otherAssignment, $submission]), [
                'note' => 'Tidak boleh diubah',
            ])
            ->assertNotFound();

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $otherAssignment, $submission]), [
                'score' => 80,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    public function test_guest_and_unrelated_lecturer_cannot_download_submission(): void
    {
        Storage::fake('local');

        [$lecturer, $student, , $assignment] = $this->createAssignmentContext();
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $path = 'submissions/'.$assignment->id.'/private.pdf';
        Storage::disk('local')->put($path, 'private submission');
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $path,
            'original_name' => 'private.pdf',
            'file_size' => 18,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        $this->get(route('mahasiswa.submissions.download', $submission))
            ->assertRedirect(route('login'));

        $this->actingAs($otherLecturer)
            ->get(route('dosen.submissions.download', $submission))
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->get(route('dosen.submissions.download', $submission))
            ->assertDownload('private.pdf');
    }

    private function createAssignmentContext(array $overrides = []): array
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);
        $course->students()->attach($student);
        $assignment = $this->createAssignment($course, $lecturer, $overrides);

        return [$lecturer, $student, $course, $assignment];
    }

    private function createAssignment(Course $course, User $lecturer, array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Submission',
            'instructions' => 'Kumpulkan jawaban.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ], $overrides));
    }

    private function createSubmission(Assignment $assignment, User $student): Submission
    {
        return Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/'.$assignment->id.'/existing.pdf',
            'original_name' => 'existing.pdf',
            'file_size' => 100,
            'submitted_at' => now(),
            'is_late' => false,
        ]);
    }
}
