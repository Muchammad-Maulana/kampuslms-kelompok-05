<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_register_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->get('/admin/users/create')->assertRedirect('/login');
        $this->actingAs($student)->post('/admin/users', [
            'name' => 'Dosen Baru',
            'email' => 'dosen.baru@example.test',
            'password' => 'password-baru',
            'role' => 'dosen',
        ])->assertForbidden();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Dosen Baru',
            'email' => 'dosen.baru@example.test',
            'password' => 'password-baru',
            'role' => 'dosen',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'dosen.baru@example.test')->firstOrFail();
        $this->assertSame('dosen', $user->role);
        $this->assertTrue(Hash::check('password-baru', $user->password));
    }

    public function test_admin_can_reset_a_users_password_and_revoke_api_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $user->createToken('test-device');

        $this->actingAs($admin)->post(route('admin.users.reset-password', $user), [
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('admin.users.show', $user));

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertDontSee('Atur ulang kata sandi');

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-baru',
            'role' => 'mahasiswa',
        ])->assertRedirect('/dashboard');
    }

    public function test_non_admin_cannot_reset_a_users_password(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherUser = User::factory()->create(['role' => 'dosen']);
        $originalPassword = $otherUser->password;

        $this->actingAs($student)->post(route('admin.users.reset-password', $otherUser), [
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertForbidden();

        $this->assertSame($originalPassword, $otherUser->fresh()->password);
    }

    public function test_logout_ends_the_authenticated_web_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
