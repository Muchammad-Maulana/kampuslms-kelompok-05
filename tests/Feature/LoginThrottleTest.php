<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_clears_email_and_password_from_old_input(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
            'role' => 'mahasiswa',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('_old_input.email');
        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionHas('_old_input.role', 'mahasiswa');
    }

    public function test_fifth_failed_login_throttles_attempts_for_one_minute(): void
    {
        $user = User::factory()->create([
            'email' => 'mahasiswa@example.test',
            'password' => 'correct-password',
            'role' => 'mahasiswa',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'role' => 'mahasiswa',
            ]);
        }

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->travel(59)->seconds();

        $blockedResponse = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'role' => 'mahasiswa',
        ]);

        $blockedResponse->assertRedirect('/login');
        $this->assertGuest();

        $this->travel(2)->seconds();

        $successResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'role' => 'mahasiswa',
        ]);

        $successResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
