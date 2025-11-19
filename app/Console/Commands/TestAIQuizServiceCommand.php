<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentProfile;
use App\Services\AIQuizService;

class TestAIQuizServiceCommand extends Command
{
    protected $signature = 'test:ai-quiz-service';
    protected $description = 'Test AI Quiz Service directly';

    public function handle()
    {
        $this->info('Testing AI Quiz Service directly...');
        
        // Create a mock student profile
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
        
        $this->info('Calling generateQuizQuestions...');
        
        // Generate quiz questions
        $result = $aiQuizService->generateQuizQuestions($studentProfile);
        
        $this->info('Result: ' . json_encode($result, JSON_PRETTY_PRINT));
        
        if ($result['success']) {
            $this->info('✅ Quiz generation successful!');
            $this->info('Message: ' . $result['message']);
            $this->info('Number of questions: ' . count($result['data']));
            $this->info('First question: ' . ($result['data'][0]['question'] ?? 'N/A'));
        } else {
            $this->error('❌ Quiz generation failed!');
            $this->error('Error: ' . $result['message']);
        }
        
        return 0;
    }
}