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

    public function test_late_allowed_submission_is_marked_late_and_updated_instead_of_duplicated(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Terlambat',
            'instructions' => 'Tugas dengan tenggat yang sudah lewat.',
            'due_at' => now()->subMinute(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        $this->travelTo(now()->addMinute());

        foreach (['jawaban-awal.pdf', 'jawaban-revisi.pdf'] as $filename) {
            $this->actingAs($student)
                ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                    'file' => UploadedFile::fake()->create($filename, 100, 'application/pdf'),
                ])
                ->assertRedirect();
        }

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->sole();

        $this->assertSame('jawaban-revisi.pdf', $submission->original_name);
        $this->assertTrue($submission->is_late);
    }

    public function test_late_submission_is_rejected_when_assignment_disallows_late_work(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Tertutup',
            'instructions' => 'Tugas tanpa pengumpulan terlambat.',
            'due_at' => now()->subMinute(),
            'max_score' => 100,
            'allow_late' => false,
            'status' => 'published',
        ]);

        $this->travelTo(now()->addMinute());

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }
}
