<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_profile()
    {
        // Create a student user
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890'
        ]);

        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'major_subject' => 'Computer Science',
            'bio' => 'Test bio'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Update profile data
        $updateData = [
            'first_name' => 'John Updated',
            'last_name' => 'Doe Updated',
            'phone' => '0987654321',
            'dob' => '2000-02-01',
            'gender' => 'male',
            'class_year' => '2025',
            'institute' => 'Updated University',
            'major_subject' => 'Updated Computer Science',
            'bio' => 'Updated bio'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/student/update-profile', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        // Verify user data was updated
        $user->refresh();
        $this->assertEquals('John Updated', $user->first_name);
        $this->assertEquals('Doe Updated', $user->last_name);
        $this->assertEquals('0987654321', $user->phone);

        // Verify profile data was updated
        $profile = $user->studentProfile;
        $this->assertEquals('2000-02-01', $profile->dob);
        $this->assertEquals('2025', $profile->class_year);
        $this->assertEquals('Updated University', $profile->institute);
    }


    public function test_validation_errors_for_invalid_data()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        // Test with invalid data
        $invalidData = [
            'gender' => 'invalid_gender',
            'dob' => 'invalid_date',
            'bio' => str_repeat('a', 1001) // Too long
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/student/update-profile', $invalidData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false
                ]);
    }
}
