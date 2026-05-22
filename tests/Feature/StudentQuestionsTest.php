<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentQuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_questions_with_array_technologies()
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
            'bio' => 'Test bio'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Questions data with array technologies
        $questionsData = [
            'major_subject' => 'Artificial Intelligence & Machine Learning',
            'current_position' => 'Working on academic projects',
            'specialization_field' => 'NLP',
            'preferred_technologies' => ['nltk', 'transformers'],
            'current_skill_level' => 'beginner',
            'main_goal' => 'Get a Programming Job',
            'time_per_week' => '2-5 hours per week'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/student/questions', $questionsData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Student questions updated and quiz generated successfully'
                ]);

        // Verify profile data was updated with formatted technologies
        $studentProfile = StudentProfile::where('user_id', $user->id)->first();
        $this->assertEquals('Artificial Intelligence & Machine Learning', $studentProfile->major_subject);
        $this->assertEquals('Working on academic projects', $studentProfile->current_position);
        $this->assertEquals('NLP', $studentProfile->specialization_field);
        $this->assertEquals('nltk,transformers', $studentProfile->preferred_technologies);
        $this->assertEquals('beginner', $studentProfile->current_skill_level);
        $this->assertEquals('Get a Programming Job', $studentProfile->main_goal);
        $this->assertEquals('2-5 hours per week', $studentProfile->time_per_week);
    }

    public function test_student_can_submit_questions_with_string_technologies()
    {
        // Create a student user
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321'
        ]);

        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'female',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio'
        ]);

        // Login the user
        $token = $user->createToken('test-token')->plainTextToken;

        // Questions data with string technologies
        $questionsData = [
            'major_subject' => 'Web Development',
            'current_position' => 'Freelancer',
            'specialization_field' => 'Backend',
            'preferred_technologies' => 'PHP,Laravel,MySQL',
            'current_skill_level' => 'intermediate',
            'main_goal' => 'Get a Full-time Job',
            'time_per_week' => '10-15 hours per week'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/student/questions', $questionsData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Student questions updated and quiz generated successfully'
                ]);

        // Verify profile data was updated with string technologies
        $studentProfile = StudentProfile::where('user_id', $user->id)->first();
        $this->assertEquals('Web Development', $studentProfile->major_subject);
        $this->assertEquals('Freelancer', $studentProfile->current_position);
        $this->assertEquals('Backend', $studentProfile->specialization_field);
        $this->assertEquals('PHP,Laravel,MySQL', $studentProfile->preferred_technologies);
        $this->assertEquals('intermediate', $studentProfile->current_skill_level);
        $this->assertEquals('Get a Full-time Job', $studentProfile->main_goal);
        $this->assertEquals('10-15 hours per week', $studentProfile->time_per_week);
    }
}