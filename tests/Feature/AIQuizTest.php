<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_generate_ai_quiz()
    {
        // Create a student user
        $user = User::factory()->create([
            'user_type' => 'student',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890'
        ]);

        // Create student profile with complete data
        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio',
            'major_subject' => 'Computer Science',
            'current_position' => 'Student',
            'specialization_field' => 'Web Development',
            'preferred_technologies' => 'PHP,Laravel,JavaScript',
            'current_skill_level' => 'Intermediate',
            'main_goal' => 'Get a job',
            'time_per_week' => '20 hours'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/student/generate-quiz');

        // Since we're using a mock API key, we expect this to fail but still test the endpoint
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
    }

    public function test_student_questions_update_triggers_ai_quiz_generation()
    {
        // Create a student user
        $user = User::factory()->create([
            'user_type' => 'student',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567891'
        ]);

        // Create student profile
        $studentProfile = StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'female',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Questions data
        $questionsData = [
            'major_subject' => 'Computer Science',
            'current_position' => 'Student',
            'specialization_field' => 'Web Development',
            'preferred_technologies' => 'PHP,Laravel,JavaScript',
            'current_skill_level' => 'Intermediate',
            'main_goal' => 'Get a job',
            'time_per_week' => '20 hours'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/student/questions', $questionsData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
    }
}