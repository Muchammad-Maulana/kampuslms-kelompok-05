<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekSevenAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_directory_searches_filters_and_preserves_pagination_query(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'name' => 'Web Search Match',
            'email' => 'web-match@example.test',
            'role' => 'dosen',
            'nim_nip' => 'DOSEN-WEB-01',
        ]);
        User::factory()->create([
            'name' => 'Web Search Student',
            'email' => 'student-match@example.test',
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Web Search Match', 'role' => 'dosen']))
            ->assertOk()
            ->assertSee('web-match@example.test')
            ->assertDontSee('student-match@example.test');

        for ($userNumber = 0; $userNumber < 20; $userNumber++) {
            User::factory()->create([
                'name' => 'Pagination User '.$userNumber,
                'role' => 'mahasiswa',
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Pagination User', 'role' => 'mahasiswa']));

        $response->assertOk()
            ->assertSee('Pagination User 19')
            ->assertDontSee('Pagination User 0')
            ->assertSee(route('admin.users.index', [
                'q' => 'Pagination User',
                'role' => 'mahasiswa',
                'page' => 2,
            ]));
    }

    public function test_only_admin_and_the_course_lecturer_can_manage_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->assertTrue($admin->can('manageEnrollment', $course));
        $this->assertTrue($lecturer->can('manageEnrollment', $course));
        $this->assertFalse($otherLecturer->can('manageEnrollment', $course));
        $this->assertFalse($student->can('manageEnrollment', $course));
    }

    public function test_dashboard_metrics_are_specific_to_each_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Dashboard published assignment',
            'instructions' => 'Instructions',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna aktif')
            ->assertSee('Tugas');

        $this->actingAs($lecturer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tugas terbit')
            ->assertSee('Pengumpulan belum dinilai');

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tugas terbit')
            ->assertSee('Tugas dikumpulkan');
    }

    public function test_admin_can_view_grade_but_only_course_lecturer_can_change_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Grade policy assignment',
            'instructions' => 'Instructions',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'test/submission.pdf',
            'original_name' => 'submission.pdf',
            'file_size' => 100,
            'submitted_at' => now(),
            'is_late' => false,
        ]);
        $grade = Grade::create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
            'score' => 90,
            'graded_at' => now(),
        ]);

        $this->assertTrue($admin->can('view', $grade));
        $this->assertFalse($admin->can('create', [Grade::class, $submission]));
        $this->assertFalse($admin->can('update', $grade));
        $this->assertTrue($lecturer->can('update', $grade));
        $this->assertFalse($otherLecturer->can('update', $grade));
    }

    public function test_sensitive_user_attributes_are_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $serialized = $user->toArray();

        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('remember_token', $serialized);
    }
}
