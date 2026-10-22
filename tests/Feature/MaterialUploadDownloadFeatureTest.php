<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialUploadDownloadFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_upload_private_material_for_enrolled_students_to_download(): void
    {
        Storage::fake('local');

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $unregisteredStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Silabus',
                'type' => 'file',
                'file' => UploadedFile::fake()->create('silabus.pdf', 500, 'application/pdf'),
            ])
            ->assertRedirect();

        $material = Material::query()->where('title', 'Silabus')->firstOrFail();
        $this->assertSame('silabus.pdf', $material->original_name);
        $this->assertGreaterThan(0, $material->file_size);
        Storage::disk('local')->assertExists($material->file_path);

        $this->actingAs($student)
            ->get(route('mahasiswa.mata-kuliah.materials.download', [$course, $material]))
            ->assertOk()
            ->assertDownload('silabus.pdf');

        $this->actingAs($unregisteredStudent)
            ->get(route('mahasiswa.mata-kuliah.materials.download', [$course, $material]))
            ->assertForbidden();
    }

    public function test_material_upload_rejects_unsupported_or_oversized_files(): void
    {
        Storage::fake('local');

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Berkas Tidak Didukung',
                'type' => 'file',
                'file' => UploadedFile::fake()->create('program.exe', 100, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.materials.store', $course), [
                'title' => 'Berkas Terlalu Besar',
                'type' => 'file',
                'file' => UploadedFile::fake()->create('besar.pdf', 20481, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('materials', 0);
    }
}