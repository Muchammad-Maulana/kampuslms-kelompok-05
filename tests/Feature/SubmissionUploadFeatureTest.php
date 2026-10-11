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

class SubmissionUploadFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_upload_submission_and_dosen_can_grade_it(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Upload',
            'instructions' => 'Unggah file Anda.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('jawaban.pdf', 500, 'application/pdf');

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => $file,
                'note' => 'Jawaban saya',
            ])
            ->assertRedirect(route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment]))
            ->assertSessionHas('success', 'Tugas berhasil dikirim.');

        $submission = Submission::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('jawaban.pdf', $submission->original_name);
        $this->assertNotNull($submission->file_path);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]), [
                'score' => 88,
                'feedback' => 'Bagus sekali',
            ])
            ->assertRedirect(route('dosen.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment]))
            ->assertSessionHas('success', 'Nilai tugas berhasil disimpan.');

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 88,
            'graded_by' => $lecturer->id,
        ]);
    }
}
