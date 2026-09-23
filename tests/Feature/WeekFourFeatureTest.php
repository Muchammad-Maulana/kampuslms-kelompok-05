<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekFourFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_index_filters_and_keeps_query_string(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        Course::factory()->create([
            'code' => 'CSI-100',
            'name' => 'Sistem Basis Data',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        Course::factory()->create([
            'code' => 'MAT-101',
            'name' => 'Matematika Dasar',
            'lecturer_id' => $dosen->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get('/mata-kuliah?q=Basis&status=active');

        $response->assertOk();
        $response->assertSee('Sistem Basis Data');
        $response->assertDontSee('Matematika Dasar');
    }

    public function test_demo_login_accepts_role_specific_account(): void
    {
        User::factory()->create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@kampuslms.test',
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $response = $this->post('/login', [
            'email' => 'mahasiswa@kampuslms.test',
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_dashboard_displays_the_logged_in_role(): void
    {
        $dosen = User::factory()->create([
            'name' => 'Dosen Penguji',
            'email' => 'dosen.penguji@example.test',
            'role' => 'dosen',
        ]);

        $response = $this->actingAs($dosen)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Selamat datang, Dosen Penguji');
        $response->assertSee('Anda masuk sebagai Dosen');
        $response->assertSee('dosen.penguji@example.test');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_crud_routes(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa)->get('/users');

        $response->assertForbidden();
    }

    public function test_contract_seeder_creates_the_required_minimum_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(3, User::where('role', 'dosen')->count());
        $this->assertSame(30, User::where('role', 'mahasiswa')->count());
        $this->assertSame(5, Course::count());
        $this->assertSame(15, Assignment::count());
        $this->assertSame(100, Submission::count());
        $this->assertSame(60, Grade::count());

        Course::query()->each(function (Course $course): void {
            $this->assertGreaterThanOrEqual(15, $course->students()->count());
        });
    }
}
