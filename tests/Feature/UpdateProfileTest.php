<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\MentorProfile;
use App\Models\ProfessionalProfile;
use App\Models\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_profile()
    {
        // Create a student user
        $user = User::factory()->create([
            'user_type' => 'student',
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
        ])->postJson('/api/auth/update-profile', $updateData);

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

    public function test_mentor_can_update_profile()
    {
        // Create a mentor user
        $user = User::factory()->create([
            'user_type' => 'mentor',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com'
        ]);

        // Create mentor profile
        MentorProfile::create([
            'user_id' => $user->id,
            'qualifications' => 'PhD in Computer Science',
            'area_of_expertise' => ['Programming', 'Algorithms'],
            'experience_years' => 10,
            'institute' => 'Tech University',
            'bio' => 'Experienced mentor'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Update profile data
        $updateData = [
            'first_name' => 'Jane Updated',
            'qualifications' => 'PhD in Computer Science, MBA',
            'area_of_expertise' => ['Programming', 'Algorithms', 'Machine Learning'],
            'experience_years' => 12,
            'institute' => 'Updated Tech University',
            'bio' => 'Updated experienced mentor'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/update-profile', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        // Verify user data was updated
        $user->refresh();
        $this->assertEquals('Jane Updated', $user->first_name);

        // Verify profile data was updated
        $profile = $user->mentorProfile;
        $this->assertEquals('PhD in Computer Science, MBA', $profile->qualifications);
        $this->assertEquals(12, $profile->experience_years);
        $this->assertEquals('Updated Tech University', $profile->institute);
    }

    public function test_professional_can_update_profile()
    {
        // Create a professional user
        $user = User::factory()->create([
            'user_type' => 'professional',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@example.com'
        ]);

        // Create professional profile
        ProfessionalProfile::create([
            'user_id' => $user->id,
            'executive_summary' => 'Experienced software engineer',
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'current_position' => 'Senior Developer',
            'year_of_experience' => '5'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Update profile data
        $updateData = [
            'first_name' => 'Bob Updated',
            'executive_summary' => 'Updated experienced software engineer',
            'skills' => ['PHP', 'Laravel', 'JavaScript', 'React', 'Node.js'],
            'current_position' => 'Lead Developer',
            'year_of_experience' => '7'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/update-profile', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        // Verify user data was updated
        $user->refresh();
        $this->assertEquals('Bob Updated', $user->first_name);

        // Verify profile data was updated
        $profile = $user->professionalProfile;
        $this->assertEquals('Updated experienced software engineer', $profile->executive_summary);
        $this->assertEquals('Lead Developer', $profile->current_position);
        $this->assertEquals('7', $profile->year_of_experience);
    }

    public function test_company_can_update_profile()
    {
        // Create a company user
        $user = User::factory()->create([
            'user_type' => 'company',
            'first_name' => 'Tech Corp',
            'last_name' => 'Inc',
            'email' => 'info@techcorp.com'
        ]);

        // Create company profile
        CompanyProfile::create([
            'user_id' => $user->id,
            'company_size' => '50-100',
            'industry' => 'Technology',
            'bio' => 'Leading tech company',
            'website_link' => 'https://techcorp.com',
            'location' => 'San Francisco, CA'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Update profile data
        $updateData = [
            'first_name' => 'Tech Corp Updated',
            'company_size' => '100-500',
            'industry' => 'Updated Technology',
            'bio' => 'Updated leading tech company',
            'website_link' => 'https://updated-techcorp.com',
            'location' => 'Updated San Francisco, CA'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/update-profile', $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);

        // Verify user data was updated
        $user->refresh();
        $this->assertEquals('Tech Corp Updated', $user->first_name);

        // Verify profile data was updated
        $profile = $user->companyProfile;
        $this->assertEquals('100-500', $profile->company_size);
        $this->assertEquals('Updated Technology', $profile->industry);
        $this->assertEquals('https://updated-techcorp.com', $profile->website_link);
    }

    public function test_validation_errors_for_invalid_data()
    {
        $user = User::factory()->create([
            'user_type' => 'student',
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
        ])->postJson('/api/auth/update-profile', $invalidData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false
                ]);
    }
}
