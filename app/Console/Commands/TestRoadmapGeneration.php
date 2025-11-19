<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\StudentProfile;
use App\Services\AIRoadmapService;

class TestRoadmapGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:roadmap-generation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the AI roadmap generation functionality';

    /**
     * Execute the console command.
     */
    public function handle(AIRoadmapService $roadmapService)
    {
        $this->info('Starting roadmap generation test...');
        
        // Create a test user and student profile
        $user = User::factory()->create([
            'user_type' => 'student',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . time() . '@example.com'
        ]);

        $this->info('Created test user with ID: ' . $user->id);

        $studentProfile = StudentProfile::create([
            'user_id' => $user->id,
            'major_subject' => 'Computer Science',
            'current_position' => 'Student',
            'specialization_field' => 'Web Development',
            'preferred_technologies' => 'PHP,Laravel,JavaScript',
            'current_skill_level' => 'Intermediate',
            'main_goal' => 'Get a job',
            'time_per_week' => '20 hours'
        ]);

        $this->info('Created student profile');

        // Test data for quizzes
        $quizData = [
            [
                'id' => 1,
                'score' => 85
            ]
        ];

        // Generate roadmap
        $this->info('Generating roadmap for test user...');
        $result = $roadmapService->generateLearningRoadmap($studentProfile, $quizData);

        if ($result['success']) {
            $this->info('Roadmap generated successfully!');
            $this->info('Roadmap length: ' . strlen($result['data']) . ' characters');
            
            // Save the roadmap
            $roadmap = $roadmapService->saveRoadmap($user->id, $result['data']);
            $this->info('Roadmap saved with ID: ' . $roadmap->id);
            
            // Display a portion of the generated roadmap
            $this->info('Generated roadmap preview:');
            $this->line(substr($result['data'], 0, 500) . '...');
        } else {
            $this->error('Failed to generate roadmap: ' . $result['message']);
        }

        $this->info('Test completed. Check storage/logs/laravel.log for detailed logs.');
    }
}