<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentProfile;
use App\Services\AIQuizService;

class TestAIQuizCommand extends Command
{
    protected $signature = 'test:ai-quiz';
    protected $description = 'Test AI Quiz Generation';

    public function handle()
    {
        $this->info('Testing AI Quiz Generation...');
        
        // Create a mock student profile for testing
        $studentProfile = new StudentProfile();
        $studentProfile->major_subject = 'Web Development';
        $studentProfile->current_position = 'Looking for a job';
        $studentProfile->specialization_field = 'Backend Development';
        $studentProfile->preferred_technologies = 'Node.js';
        $studentProfile->current_skill_level = 'Beginner';
        $studentProfile->main_goal = 'Get a job';
        $studentProfile->time_per_week = '5-10 hours';

        // Initialize the AI Quiz Service
        $aiQuizService = new AIQuizService();

        $this->info('API Key: ' . env('HUGGINGFACE_API_KEY'));
        $this->info('API URL: ' . env('HUGGINGFACE_URL'));

        // Generate quiz questions
        $result = $aiQuizService->generateQuizQuestions($studentProfile);

        $this->info('Result:');
        $this->info(print_r($result, true));

        if ($result['success']) {
            $this->info('✅ Quiz generation successful!');
            $this->info('Message: ' . $result['message']);
            $this->info('Questions:');
            $this->info(print_r($result['data'], true));
        } else {
            $this->error('❌ Quiz generation failed!');
            $this->error('Error: ' . $result['message']);
        }
        
        return 0;
    }
}