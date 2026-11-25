<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeekNineFileHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_uploads_use_random_private_paths_and_resubmission_replaces_the_old_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        [$student, $lecturer, $course, $assignment] = $this->createAssignmentContext();

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $submission = Submission::query()->where('user_id', $student->id)->firstOrFail();
        $oldPath = $submission->file_path;

        $this->assertStringStartsWith('submissions/'.$assignment->id.'/', $oldPath);
        $this->assertStringNotContainsString('jawaban.pdf', $oldPath);
        Storage::disk('local')->assertExists($oldPath);
        Storage::disk('public')->assertMissing($oldPath);

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(1, Submission::query()->where('user_id', $student->id)->count());
        $this->assertNotSame($oldPath, $submission->file_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($submission->file_path);
    }

    public function test_only_submission_owner_course_lecturer_and_admin_can_download_submission(): void
    {
        Storage::fake('local');

        [$student, $lecturer, , $assignment] = $this->createAssignmentContext();
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $path = 'submissions/'.$assignment->id.'/random-file.pdf';
        Storage::disk('local')->put($path, 'test file');
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $path,
            'original_name' => 'jawaban-asli.pdf',
            'file_size' => 9,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.submissions.download', $submission))
            ->assertDownload('jawaban-asli.pdf');
        $this->actingAs($lecturer)
            ->get(route('dosen.submissions.download', $submission))
            ->assertDownload('jawaban-asli.pdf');
        $this->actingAs($admin)
            ->get(route('admin.submissions.download', $submission))
            ->assertDownload('jawaban-asli.pdf');
        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.submissions.download', $submission))
            ->assertForbidden();
    }

    public function test_late_submission_is_rejected_when_assignment_disallows_late_work(): void
    {
        Storage::fake('local');

        [$student, , $course, $assignment] = $this->createAssignmentContext([
            'due_at' => now()->subMinute(),
            'allow_late' => false,
        ]);

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_unenrolled_student_cannot_submit_assignment(): void
    {
        Storage::fake('local');

        [, , $course, $assignment] = $this->createAssignmentContext();
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($otherStudent)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);
    }

    public function test_late_status_is_computed_by_server(): void
    {
        Storage::fake('local');

        [$student, , $course, $assignment] = $this->createAssignmentContext([
            'due_at' => now()->subMinute(),
            'allow_late' => true,
        ]);

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
                'is_late' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'is_late' => true,
        ]);
    }

    public function test_student_assignment_list_shows_late_and_on_time_submissions_with_distinct_statuses(): void
    {
        [$student, $lecturer, $course, $lateAssignment] = $this->createAssignmentContext([
            'due_at' => now()->subDays(2),
            'allow_late' => true,
        ]);
        $onTimeAssignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas tepat waktu',
            'instructions' => 'Kirim sebelum tenggat.',
            'due_at' => now()->subDays(3),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        Submission::create([
            'assignment_id' => $lateAssignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/late.pdf',
            'original_name' => 'late.pdf',
            'file_size' => 100,
            'submitted_at' => now()->subDay(),
            'is_late' => true,
        ]);
        Submission::create([
            'assignment_id' => $onTimeAssignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/on-time.pdf',
            'original_name' => 'on-time.pdf',
            'file_size' => 100,
            'submitted_at' => $onTimeAssignment->due_at->copy()->subHour(),
            'is_late' => false,
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.mata-kuliah.assignments.index', $course))
            ->assertOk()
            ->assertSee('Terlambat')
            ->assertSee('Tepat waktu')
            ->assertSee('bg-red-100 text-red-700')
            ->assertSee('bg-emerald-100 text-emerald-700')
            ->assertSee('data-assignment-filter="late"', false);

        $this->actingAs($lecturer)
            ->get(route('dosen.assignments.show', $lateAssignment))
            ->assertOk()
            ->assertSee('Terlambat dikumpulkan')
            ->assertSee('bg-red-100 text-red-700');
    }

    public function test_submission_rejects_invalid_file_types_and_files_over_five_megabytes(): void
    {
        Storage::fake('local');

        [$student, , $course, $assignment] = $this->createAssignmentContext();

        $this->actingAs($student)
            ->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
                'file' => UploadedFile::fake()->create('script.pdf', 1, 'text/x-php'),
            ])
            ->assertSessionHasErrors('file')
            ->assertSessionHasErrors([
                'file' => 'Format file harus PDF, DOC, atau DOCX.',
            ]);

        $this->get(route('mahasiswa.mata-kuliah.assignments.scoped-show', ['course' => $course, 'assignment' => $assignment]))
            ->assertOk()
            ->assertSee('Pengumpulan belum berhasil.')
            ->assertSee('Format file harus PDF, DOC, atau DOCX.');

        $this->post(route('mahasiswa.mata-kuliah.assignments.submissions.store', [$course, $assignment]), [
            'file' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_material_files_are_stored_privately_downloaded_through_policy_and_deleted_with_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $secondStudent = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach([$student->id, $secondStudent->id]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Materi privat',
                'type' => 'file',
                'file' => UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $material = Material::query()->where('title', 'Materi privat')->firstOrFail();
        $path = $material->file_path;
        $this->assertStringStartsWith('materials/'.$course->id.'/', $path);
        $this->assertStringNotContainsString('materi.pdf', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $this->actingAs($student)
            ->get(route('mahasiswa.mata-kuliah.materials.index', $course))
            ->assertOk()
            ->assertSee('Materi privat');

        $this->get(route('mahasiswa.mata-kuliah.show', $course))
            ->assertOk()
            ->assertSee('Materi tersedia')
            ->assertSee('Materi privat');

        $this->actingAs($secondStudent)
            ->get(route('mahasiswa.mata-kuliah.materials.index', $course))
            ->assertOk()
            ->assertSee('Materi privat');
        $this->get(route('mahasiswa.mata-kuliah.show', $course))
            ->assertOk()
            ->assertSee('Materi privat');

        $this->actingAs($student)
            ->get(route('mahasiswa.materials.download', $material))
            ->assertDownload('materi.pdf');
        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.materials.download', $material))
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->delete(route('dosen.materials.destroy', $material))
            ->assertRedirect();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_course_deletion_cleans_up_private_material_and_submission_files(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        [$student, $lecturer, $course, $assignment] = $this->createAssignmentContext();
        $materialPath = 'materials/'.$course->id.'/material-random.pdf';
        $submissionPath = 'submissions/'.$assignment->id.'/submission-random.pdf';
        Storage::disk('local')->put($materialPath, 'material');
        Storage::disk('local')->put($submissionPath, 'submission');
        Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi cascade',
            'type' => 'file',
            'file_path' => $materialPath,
            'original_name' => 'materi.pdf',
            'file_size' => 8,
        ]);
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $submissionPath,
            'original_name' => 'submission.pdf',
            'file_size' => 10,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $course))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        Storage::disk('local')->assertMissing($materialPath);
        Storage::disk('local')->assertMissing($submissionPath);
    }

    public function test_lecturer_assignment_view_lists_students_who_have_not_submitted(): void
    {
        [$student, , $course, $assignment] = $this->createAssignmentContext();
        $notSubmitted = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Belum Mengumpulkan']);
        $course->students()->attach($notSubmitted);

        $this->actingAs($assignment->course->lecturer)
            ->get(route('dosen.mata-kuliah.assignments.scoped-show', [$course, $assignment]))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($notSubmitted->name)
            ->assertSee('Belum mengumpulkan');
    }

    private function createAssignmentContext(array $overrides = []): array
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);

        $assignment = Assignment::create(array_merge([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Minggu 9',
            'instructions' => 'Unggah jawaban.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ], $overrides));

        return [$student, $lecturer, $course, $assignment];
    }
}
