<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_login_and_email_verification_work(): void
    {
        Mail::fake();

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio',
        ];

        $signupResponse = $this->postJson('/api/auth/signup', $payload);

        $signupResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'status',
                        'student_profile',
                    ],
                    'token',
                ],
            ]);

        $user = User::where('email', $payload['email'])->firstOrFail();

        $this->get(
            URL::temporarySignedRoute('verification.verify', now()->addDay(), [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ])
        )->assertOk()
            ->assertSee('Email verified');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $loginResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    public function test_password_recovery_flow_works(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Recover',
            'last_name' => 'User',
            'email' => 'recover@example.com',
            'phone' => '15551234567',
            'password' => Hash::make('OldPassword@123'),
            'status' => 'approved',
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio',
        ]);

        Mail::fake();

        $forgotResponse = $this->postJson('/api/auth/forgot/password', [
            'email' => $user->email,
        ]);

        $forgotResponse->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $user->forceFill([
            'reset_password_otp' => '123456',
            'reset_password_otp_expiry' => now()->addMinutes(10),
        ])->save();

        $verifyResponse = $this->postJson('/api/auth/verify-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $verifyResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified, you can reset your password now.',
            ]);

        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'otp' => '123456',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $resetResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password has been successfully updated.',
            ]);

        $this->assertTrue(Hash::check('NewPassword@123', $user->fresh()->password));
    }

    public function test_authenticated_account_routes_work(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'account@example.com',
            'phone' => '15557654321',
            'password' => Hash::make('OldPassword@123'),
            'status' => 'approved',
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'major_subject' => 'Computer Science',
            'current_skill_level' => 'Beginner',
            'main_goal' => 'Get a job',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)->getJson('/api/student/profile')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User profile fetched successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/change-password', [
            'current_password' => 'OldPassword@123',
            'password' => 'BrandNewPassword@123',
            'password_confirmation' => 'BrandNewPassword@123',
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Password Updated Successfully',
          ]);

        $this->withHeaders($headers)->postJson('/api/student/update-profile', [
            'first_name' => 'John Updated',
            'last_name' => 'Doe Updated',
            'phone' => '15550001111',
            'dob' => '2000-02-01',
            'gender' => 'male',
            'class_year' => '2025',
            'institute' => 'Updated University',
            'major_subject' => 'Updated Computer Science',
            'bio' => 'Updated bio',
            'current_position' => 'Student',
            'specialization_field' => 'Backend',
            'preferred_technologies' => ['PHP', 'Laravel'],
            'current_skill_level' => 'Intermediate',
            'main_goal' => 'Get a better job',
            'time_per_week' => '10 hours',
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Profile updated successfully',
          ]);

        $this->withHeaders($headers)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out from current device successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/auth/logout-all')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful from all devices',
            ]);
    }
}