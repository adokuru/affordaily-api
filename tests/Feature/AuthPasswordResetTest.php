<?php

namespace Tests\Feature;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_stores_reset_otp_for_existing_user(): void
    {
        Mail::fake();

        User::create([
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'password' => Hash::make('old-password'),
            'role' => 'receptionist',
        ]);

        $this->postJson('/api/v1/password/forgot', [
            'email' => 'frontdesk@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'frontdesk@example.com',
            'consumed_at' => null,
            'attempts' => 0,
        ]);

        $this->assertNotNull(PasswordResetOtp::first()->expires_at);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        $this->postJson('/api/v1/password/forgot', [
            'email' => 'missing@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $user = User::create([
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'password' => Hash::make('old-password'),
            'role' => 'receptionist',
        ]);
        $token = $user->createToken('auth_token');

        PasswordResetOtp::create([
            'email' => 'frontdesk@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/password/reset', [
            'email' => 'frontdesk@example.com',
            'otp' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotNull(PasswordResetOtp::first()->consumed_at);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_invalid_otp_is_rejected_and_attempt_count_increments(): void
    {
        User::create([
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'password' => Hash::make('old-password'),
            'role' => 'receptionist',
        ]);

        PasswordResetOtp::create([
            'email' => 'frontdesk@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/password/verify-otp', [
            'email' => 'frontdesk@example.com',
            'otp' => '654321',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(1, PasswordResetOtp::first()->attempts);
    }
}
