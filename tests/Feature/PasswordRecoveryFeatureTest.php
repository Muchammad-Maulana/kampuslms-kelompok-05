<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_has_same_response_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known@example.test']);

        $knownResponse = $this->post(route('password.email'), ['email' => $user->email]);
        $unknownResponse = $this->post(route('password.email'), ['email' => 'unknown@example.test']);

        $message = 'Jika email terdaftar, instruksi reset kata sandi akan dikirim.';
        $knownResponse->assertRedirect()->assertSessionHas('status', $message);
        $unknownResponse->assertRedirect()->assertSessionHas('status', $message);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_valid_reset_token_changes_password_and_revokes_api_tokens(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $user->createToken('reset-test');
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Kata sandi berhasil diubah. Silakan masuk.');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_reset_token_does_not_change_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $oldPassword = $user->password;

        $this->post(route('password.update'), [
            'token' => 'not-a-valid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($oldPassword, $user->fresh()->password);
    }
}