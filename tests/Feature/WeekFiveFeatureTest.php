<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekFiveFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_admin_role_receives_a_safe_forbidden_page(): void
    {
        $student = User::factory()->create([
            'name' => 'Mahasiswa Uji',
            'role' => 'mahasiswa',
        ]);

        $response = $this->actingAs($student)->get('/admin/users');

        $response->assertForbidden();
        $response->assertSee('Akses ditolak');
        $response->assertDontSee('Mahasiswa Uji');
    }

    public function test_lecturer_course_list_only_contains_courses_they_teach(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $ownCourse = Course::factory()->create([
            'name' => 'Course Dosen Saya',
            'lecturer_id' => $lecturer->id,
        ]);
        Course::factory()->create([
            'name' => 'Course Dosen Lain',
            'lecturer_id' => $otherLecturer->id,
        ]);

        $response = $this->actingAs($lecturer)->get(route('dosen.mata-kuliah.index'));

        $response->assertOk();
        $response->assertSee($ownCourse->name);
        $response->assertDontSee('Course Dosen Lain');
    }

    public function test_lecturer_cannot_view_another_lecturers_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $response = $this->actingAs($lecturer)->get(route('dosen.mata-kuliah.show', $course));

        $response->assertForbidden();
    }

    public function test_student_can_only_view_enrolled_courses(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->actingAs($student)->get(route('mahasiswa.mata-kuliah.show', $course));

        $response->assertForbidden();

        $course->students()->attach($student);

        $response = $this->actingAs($student)->get(route('mahasiswa.mata-kuliah.show', $course));

        $response->assertOk();
        $response->assertSee($course->name);
    }

    public function test_lecturer_cannot_view_material_from_another_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $otherLecturer->id,
            'title' => 'Materi Privat Course Lain',
            'type' => 'link',
            'external_url' => 'https://example.test/material',
        ]);

        $response = $this->actingAs($lecturer)->get(route('dosen.materials.show', $material));

        $response->assertForbidden();
    }

    public function test_student_cannot_view_draft_assignment(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Draft',
            'instructions' => 'Instruksi belum dipublikasikan.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.assignments.show', $assignment));

        $response->assertNotFound();
        $response->assertDontSee('Instruksi belum dipublikasikan.');
    }

    public function test_student_course_detail_does_not_reveal_draft_assignment_metadata(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Judul Tugas Rahasia',
            'instructions' => 'Instruksi draft.',
            'due_at' => now()->addDay(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.mata-kuliah.show', $course));

        $response->assertOk();
        $response->assertDontSee('Judul Tugas Rahasia');
        $response->assertDontSee('Instruksi draft.');
    }

    public function test_nested_assignment_list_contains_only_the_selected_courses_assignments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Course Terpilih',
            'instructions' => 'Instruksi A',
            'due_at' => now()->addDay(),
            'status' => 'published',
        ]);
        Assignment::create([
            'course_id' => $otherCourse->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Course Lain',
            'instructions' => 'Instruksi B',
            'due_at' => now()->addDay(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mata-kuliah.assignments.index', $course));

        $response->assertOk();
        $response->assertSee($assignment->title);
        $response->assertDontSee('Tugas Course Lain');
    }

    public function test_scoped_nested_route_rejects_an_assignment_from_another_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::create([
            'course_id' => $otherCourse->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Course Lain',
            'instructions' => 'Jangan tampilkan.',
            'due_at' => now()->addDay(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->get(route(
            'admin.mata-kuliah.assignments.scoped-show',
            [$course, $assignment]
        ));

        $response->assertNotFound();
        $response->assertDontSee('Jangan tampilkan.');
    }

    public function test_enrolled_student_can_view_course_materials(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Minggu Lima',
            'type' => 'link',
            'external_url' => 'https://example.test/minggu-lima',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.mata-kuliah.materials.index', $course));

        $response->assertOk();
        $response->assertSee('Materi Minggu Lima');
    }

    public function test_lecturer_dashboard_only_shows_their_courses_and_role_navigation(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $ownCourse = Course::factory()->create([
            'name' => 'Dashboard Course Saya',
            'lecturer_id' => $lecturer->id,
        ]);
        Course::factory()->create([
            'name' => 'Dashboard Course Lain',
            'lecturer_id' => $otherLecturer->id,
        ]);

        $response = $this->actingAs($lecturer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($ownCourse->name);
        $response->assertDontSee('Dashboard Course Lain');
        $response->assertSee(route('dosen.mata-kuliah.index'));
    }

    public function test_student_dashboard_only_shows_enrolled_courses(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $enrolledCourse = Course::factory()->create([
            'name' => 'Dashboard Course Terdaftar',
            'lecturer_id' => $lecturer->id,
        ]);
        $otherCourse = Course::factory()->create([
            'name' => 'Dashboard Course Tidak Terdaftar',
            'lecturer_id' => $lecturer->id,
        ]);
        $enrolledCourse->students()->attach($student);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($enrolledCourse->name);
        $response->assertDontSee($otherCourse->name);
        $response->assertSee(route('mahasiswa.mata-kuliah.index'));
    }
}