<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_read_update_and_delete_courses_through_web_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), [
                'code' => 'SI101',
                'name' => 'Pemrograman Web',
                'description' => 'Mata kuliah wajib.',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $course = Course::query()->where('code', 'SI101')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.index'))
            ->assertOk()
            ->assertSee('Pemrograman Web');

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.show', $course))
            ->assertOk()
            ->assertSee('Pemrograman Web');

        $this->actingAs($admin)
            ->get(route('admin.mata-kuliah.edit', $course))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.mata-kuliah.update', $course), [
                'name' => 'Pemrograman Web Lanjut',
                'sks' => 4,
                'status' => 'archived',
            ])
            ->assertRedirect(route('admin.mata-kuliah.show', $course));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Pemrograman Web Lanjut',
            'sks' => 4,
            'status' => 'archived',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.mata-kuliah.destroy', $course))
            ->assertRedirect(route('admin.mata-kuliah.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_dosen_and_mahasiswa_cannot_manage_courses_through_admin_routes(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'name' => 'Mata Kuliah Aman',
        ]);

        foreach ([$lecturer, $student] as $user) {
            $this->actingAs($user)
                ->get(route('admin.mata-kuliah.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('admin.mata-kuliah.store'), [
                    'code' => 'SI999',
                    'name' => 'Tidak boleh dibuat',
                    'sks' => 3,
                    'lecturer_id' => $lecturer->id,
                    'status' => 'active',
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('admin.mata-kuliah.update', $course), ['name' => 'Tidak boleh diubah'])
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('admin.mata-kuliah.destroy', $course))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Mata Kuliah Aman',
        ]);
        $this->assertDatabaseMissing('courses', ['code' => 'SI999']);
    }

    public function test_guest_is_redirected_from_admin_course_management_routes(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->get(route('admin.mata-kuliah.index'))->assertRedirect(route('login'));
        $this->get(route('admin.mata-kuliah.create'))->assertRedirect(route('login'));
        $this->post(route('admin.mata-kuliah.store'), [])->assertRedirect(route('login'));
        $this->put(route('admin.mata-kuliah.update', $course), [])->assertRedirect(route('login'));
        $this->delete(route('admin.mata-kuliah.destroy', $course))->assertRedirect(route('login'));

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_course_validation_rejects_duplicate_code_and_non_lecturer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        Course::factory()->create([
            'code' => 'SI101',
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), [
                'code' => 'SI101',
                'name' => 'Duplikat',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)
            ->post(route('admin.mata-kuliah.store'), [
                'code' => 'SI102',
                'name' => 'Dosen Tidak Valid',
                'sks' => 3,
                'lecturer_id' => $student->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('lecturer_id');

        $this->assertDatabaseMissing('courses', ['code' => 'SI102']);
    }
}
