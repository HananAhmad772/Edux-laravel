<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_store_quiz_data()
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
            'bio' => 'Test bio'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Quiz data
        $quizData = [
            'questions' => 'What is PHP?',
            'answers' => 'PHP is a server-side scripting language',
            'score' => 95.5
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/student/quiz', $quizData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Student quiz stored successfully'
                ]);

        // Verify quiz data was stored
        $this->assertDatabaseHas('student_quiz', [
            'student_id' => $user->id,
            'questions' => 'What is PHP?',
            'answers' => 'PHP is a server-side scripting language',
            'score' => 95.5
        ]);
    }

    public function test_student_can_update_questions()
    {
        // Create a student user
        $user = User::factory()->create([
            'user_type' => 'student',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john2@example.com',
            'phone' => '1234567891'
        ]);

        // Create student profile
        $studentProfile = StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
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
                    'message' => 'Student questions updated successfully'
                ]);

        // Verify profile data was updated
        $studentProfile->refresh();
        $this->assertEquals('Computer Science', $studentProfile->major_subject);
        $this->assertEquals('Student', $studentProfile->current_position);
        $this->assertEquals('Web Development', $studentProfile->specialization_field);
        $this->assertEquals('PHP,Laravel,JavaScript', $studentProfile->preferred_technologies);
        $this->assertEquals('Intermediate', $studentProfile->current_skill_level);
        $this->assertEquals('Get a job', $studentProfile->main_goal);
        $this->assertEquals('20 hours', $studentProfile->time_per_week);
    }
}