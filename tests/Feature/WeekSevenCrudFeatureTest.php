<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekSevenCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_manage_material_metadata_for_their_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $this->actingAs($lecturer)
            ->get(route('dosen.mata-kuliah.materials.create', $course))
            ->assertOk();

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Materi Link Baru',
                'description' => 'Metadata materi minggu ini.',
                'type' => 'link',
                'external_url' => 'https://example.test/materi',
                'course_id' => $otherCourse->id,
                'uploaded_by' => $otherLecturer->id,
                'file_path' => 'forbidden/path.pdf',
            ])
            ->assertRedirect();

        $material = Material::query()->where('title', 'Materi Link Baru')->firstOrFail();
        $this->assertSame($course->id, $material->course_id);
        $this->assertSame($lecturer->id, $material->uploaded_by);
        $this->assertNull($material->file_path);

        $this->actingAs($lecturer)
            ->put(route('dosen.materials.update', $material), [
                'title' => 'Materi Link Diperbarui',
                'uploaded_by' => $otherLecturer->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => 'Materi Link Diperbarui',
            'uploaded_by' => $lecturer->id,
        ]);

        $this->actingAs($lecturer)
            ->delete(route('dosen.materials.destroy', $material))
            ->assertRedirect(route('dosen.mata-kuliah.materials.index', $course));

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_lecturer_cannot_create_or_modify_materials_in_another_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
            'title' => 'Materi Dosen Lain',
            'type' => 'link',
            'external_url' => 'https://example.test/private',
        ]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Tidak boleh dibuat',
                'type' => 'link',
                'external_url' => 'https://example.test/forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->put(route('dosen.materials.update', $material), ['title' => 'Tidak boleh diubah'])
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->delete(route('dosen.materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'title' => 'Materi Dosen Lain']);
    }

    public function test_lecturer_can_manage_assignments_for_their_course_without_changing_ownership(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $this->actingAs($lecturer)
            ->get(route('dosen.mata-kuliah.assignments.create', $course))
            ->assertOk();

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.store', $course), [
                'title' => 'Tugas Baru',
                'instructions' => 'Kerjakan latihan.',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'max_score' => 90,
                'allow_late' => '0',
                'status' => 'draft',
                'course_id' => $otherCourse->id,
                'created_by' => $otherLecturer->id,
            ])
            ->assertRedirect();

        $assignment = Assignment::query()->where('title', 'Tugas Baru')->firstOrFail();
        $this->assertSame($course->id, $assignment->course_id);
        $this->assertSame($lecturer->id, $assignment->created_by);

        $this->actingAs($lecturer)
            ->put(route('dosen.assignments.update', $assignment), [
                'title' => 'Tugas Diperbarui',
                'status' => 'published',
                'created_by' => $otherLecturer->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Tugas Diperbarui',
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);

        $this->actingAs($lecturer)
            ->delete(route('dosen.assignments.destroy', $assignment))
            ->assertRedirect(route('dosen.mata-kuliah.assignments.index', $course));

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_lecturer_cannot_create_or_modify_assignments_in_another_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
            'title' => 'Tugas Dosen Lain',
            'instructions' => 'Instruksi rahasia.',
            'due_at' => now()->addDay(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'draft',
        ]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.store', $course), [
                'title' => 'Tidak boleh dibuat',
                'instructions' => 'Tidak boleh.',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'max_score' => 100,
                'allow_late' => '1',
                'status' => 'draft',
            ])
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->put(route('dosen.assignments.update', $assignment), ['title' => 'Tidak boleh diubah'])
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->delete(route('dosen.assignments.destroy', $assignment))
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'title' => 'Tugas Dosen Lain']);
    }

    public function test_invalid_material_and_assignment_metadata_is_rejected(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Link tanpa URL',
                'type' => 'link',
            ])
            ->assertSessionHasErrors(['external_url']);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.store', $course), [
                'title' => 'Nilai invalid',
                'instructions' => 'Instruksi.',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'max_score' => 101,
                'allow_late' => '1',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors(['max_score']);
    }

    public function test_student_cannot_change_a_users_role_through_admin_update_route(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $target = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($student)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'dosen',
        ]);
    }
}