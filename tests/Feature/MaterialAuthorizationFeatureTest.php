<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialAuthorizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_create_update_or_delete_materials_through_lecturer_routes(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Aman',
            'type' => 'link',
            'external_url' => 'https://example.test/aman',
        ]);

        $this->actingAs($student)
            ->get(route('dosen.mata-kuliah.materials.create', $course))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Tidak boleh dibuat',
                'type' => 'link',
                'external_url' => 'https://example.test/forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('dosen.materials.update', $material), ['title' => 'Tidak boleh diubah'])
            ->assertForbidden();

        $this->actingAs($student)
            ->delete(route('dosen.materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => 'Materi Aman',
        ]);
    }

    public function test_guest_and_unrelated_lecturer_cannot_download_material(): void
    {
        Storage::fake('local');

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $path = 'materials/'.$course->id.'/private.pdf';
        Storage::disk('local')->put($path, 'private material');
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Privat',
            'type' => 'file',
            'file_path' => $path,
            'original_name' => 'private.pdf',
            'file_size' => 16,
            'mime_type' => 'application/pdf',
        ]);

        $this->get(route('mahasiswa.materials.download', $material))
            ->assertRedirect(route('login'));

        $this->actingAs($otherLecturer)
            ->get(route('dosen.materials.download', $material))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('mahasiswa.materials.download', $material))
            ->assertDownload('private.pdf');
    }

    public function test_scoped_material_route_rejects_material_from_another_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $course->students()->attach($student);
        $otherMaterial = Material::create([
            'course_id' => $otherCourse->id,
            'uploaded_by' => $otherLecturer->id,
            'title' => 'Materi Course Lain',
            'type' => 'link',
            'external_url' => 'https://example.test/lain',
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.mata-kuliah.materials.scoped-show', [$course, $otherMaterial]))
            ->assertNotFound();
    }
}
