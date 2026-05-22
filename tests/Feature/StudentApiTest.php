<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\StudentProfile;
use App\Models\StudentRoadmap;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\ExtendedDashboardService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_service_routes_work_with_mocked_profile_service(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'student@example.com',
            'phone' => '15550000001',
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
            'bio' => 'Test bio',
        ]);

        $token = $user->createToken('student-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $profileService = $this->mock(ProfileService::class);
        $profileService->shouldReceive('updateStudentQuestions')->once()->andReturn([
            'status' => true,
            'message' => 'Student questions updated and quiz generated successfully',
            'data' => ['profile' => ['id' => $user->id], 'quiz' => ['id' => 'quiz-1']],
            'code' => 200,
        ]);
        $profileService->shouldReceive('storeStudentQuiz')->once()->andReturn([
            'status' => true,
            'message' => 'Student quiz stored successfully',
            'data' => ['id' => 'quiz-2'],
            'code' => 201,
        ]);
        $profileService->shouldReceive('getStudentQuizzes')->once()->andReturn([
            'status' => true,
            'message' => 'Student quizzes retrieved successfully',
            'data' => [['id' => 'quiz-1']],
            'code' => 200,
        ]);
        $profileService->shouldReceive('generateAIQuiz')->once()->andReturn([
            'status' => true,
            'message' => 'AI quiz generated successfully',
            'data' => ['questions' => [['id' => 1]]],
            'code' => 200,
        ]);
        $profileService->shouldReceive('generatePersonalizedRoadmap')->once()->andReturn([
            'status' => true,
            'message' => 'Personalized learning roadmap generated successfully',
            'data' => ['id' => 'roadmap-1'],
            'code' => 200,
        ]);
        $profileService->shouldReceive('getStudentRoadmaps')->once()->andReturn([
            'status' => true,
            'message' => 'Student roadmaps retrieved successfully',
            'data' => [['id' => 'roadmap-1']],
            'code' => 200,
        ]);
        $profileService->shouldReceive('getLatestStudentRoadmap')->once()->andReturn([
            'status' => true,
            'message' => 'Latest student roadmap retrieved successfully',
            'data' => ['id' => 'roadmap-1'],
            'code' => 200,
        ]);
        $profileService->shouldReceive('getCurrentRoadmapWithTopics')->once()->andReturn([
            'status' => true,
            'message' => 'Current roadmap with topics retrieved successfully',
            'data' => [
                'day_wise_roadmap' => ['day_1' => ['topic' => 'PHP']],
                'current_progress' => null,
                'student' => $user->fresh(['studentProfile']),
            ],
            'code' => 200,
        ]);
        $profileService->shouldReceive('advanceUserProgress')->once()->andReturn([
            'status' => true,
            'message' => 'User progress advanced successfully',
            'data' => ['current_topic_index' => 2],
            'code' => 200,
        ]);
        $profileService->shouldReceive('chatWithAI')->once()->andReturn([
            'status' => true,
            'message' => 'AI response generated successfully',
            'data' => ['response' => 'Hello from AI'],
            'code' => 200,
        ]);
        $profileService->shouldReceive('generateDailyChallenge')->once()->andReturn([
            'status' => true,
            'message' => 'Daily challenge generated successfully',
            'data' => [
                'id' => 'challenge-1',
                'topic_name' => 'PHP',
                'challenge_description' => 'Build something',
                'challenge_data' => [
                    'instructions' => 'Follow the steps',
                    'expected_outcome' => 'A working result',
                    'tips' => 'Keep it simple',
                    'difficulty' => 'Easy',
                    'estimated_time' => '10 minutes',
                    'solution' => 'echo',
                ],
                'is_completed' => false,
                'points_earned' => 10,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
            'code' => 200,
        ]);
        $profileService->shouldReceive('evaluateDailyChallengeSubmission')->once()->andReturn([
            'status' => true,
            'message' => 'Daily challenge submission evaluated successfully',
            'data' => [
                'id' => 'challenge-1',
                'topic_name' => 'PHP',
                'challenge_description' => 'Build something',
                'challenge_data' => [
                    'instructions' => 'Follow the steps',
                    'expected_outcome' => 'A working result',
                    'tips' => 'Keep it simple',
                    'difficulty' => 'Easy',
                    'estimated_time' => '10 minutes',
                    'solution' => 'echo',
                ],
                'student_submission' => 'My answer',
                'ai_feedback' => 'Good job',
                'is_completed' => true,
                'points_earned' => 10,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
            'code' => 200,
        ]);

        $this->withHeaders($headers)->postJson('/api/student/questions', [
            'major_subject' => 'Computer Science',
            'current_position' => 'Student',
            'specialization_field' => 'Web Development',
            'preferred_technologies' => ['PHP', 'Laravel'],
            'current_skill_level' => 'Intermediate',
            'main_goal' => 'Get a job',
            'time_per_week' => '20 hours',
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Student questions updated and quiz generated successfully',
          ]);

        $this->withHeaders($headers)->postJson('/api/student/quiz', [
            'questions' => 'What is PHP?',
            'answers' => 'A server-side language',
            'score' => 95,
        ])->assertCreated()
          ->assertJson([
              'success' => true,
              'message' => 'Student quiz stored successfully',
          ]);

        $this->withHeaders($headers)->getJson('/api/student/quizzes')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Student quizzes retrieved successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/generate-quiz')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'AI quiz generated successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/generate-roadmap')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Personalized learning roadmap generated successfully',
            ]);

        $this->withHeaders($headers)->getJson('/api/student/roadmaps')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Student roadmaps retrieved successfully',
            ]);

        $this->withHeaders($headers)->getJson('/api/student/roadmap/latest')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Latest student roadmap retrieved successfully',
            ]);

        $this->withHeaders($headers)->getJson('/api/student/roadmap/current')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Current roadmap with topics retrieved successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/roadmap/advance')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User progress advanced successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/chatbot', [
            'messages' => [
                ['role' => 'user', 'content' => 'Explain PHP'],
            ],
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'AI response generated successfully',
          ]);

        $this->withHeaders($headers)->getJson('/api/student/daily-challenge')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Daily challenge generated successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/student/daily-challenge/submit', [
            'challenge_id' => 'challenge-1',
            'submission' => 'My answer',
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Daily challenge submission evaluated successfully',
          ]);
    }

    public function test_student_chat_history_returns_saved_messages(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'history@example.com',
            'phone' => '15550000002',
            'status' => 'approved',
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'female',
            'class_year' => '2024',
            'institute' => 'Test University',
            'major_subject' => 'Computer Science',
            'current_skill_level' => 'Beginner',
            'main_goal' => 'Get a job',
        ]);

        Message::create([
            'user_id' => $user->id,
            'roadmap_id' => null,
            'message_body' => 'Hello',
            'role' => 'user',
        ]);

        Message::create([
            'user_id' => $user->id,
            'roadmap_id' => null,
            'message_body' => 'Hi there',
            'role' => 'assistant',
        ]);

        $token = $user->createToken('student-token')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/student/chat/history')
          ->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Chat history retrieved successfully',
          ])
          ->assertJsonCount(2, 'data');
    }

    public function test_student_progress_route_returns_dashboard_payload(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Progress',
            'last_name' => 'User',
            'email' => 'progress@example.com',
            'phone' => '15550000003',
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

        StudentRoadmap::create([
            'student_id' => $user->id,
            'roadmap_content' => 'Sample roadmap',
            'roadmap_json' => ['steps' => []],
        ]);

        UserProgress::create([
            'user_id' => $user->id,
            'roadmap_id' => null,
            'current_step' => 'Week 1–2',
            'current_topic_index' => 1,
            'last_active_at' => now(),
        ]);

        $dashboardMock = \Mockery::mock('overload:App\Services\ExtendedDashboardService');
        $dashboardMock->shouldReceive('getExtendedDashboardData')->once()->andReturn([
            'status' => true,
            'message' => 'Extended dashboard data retrieved successfully',
            'data' => [
                'weekly_activity' => [],
                'skill_mastery' => [],
                'ai_insights' => [],
                'feedback_history' => [],
            ],
            'code' => 200,
        ]);

        $token = $user->createToken('student-token')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/student/progress')
          ->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Extended dashboard data retrieved successfully',
          ]);
    }
}