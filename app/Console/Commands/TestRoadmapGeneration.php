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
    protected $signature = 'test:roadmap-generation {--user= : User ID to test with}';

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
        
        $userId = $this->option('user');
        
        if ($userId) {
            // Use existing user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            $studentProfile = StudentProfile::where('user_id', $userId)->first();
            if (!$studentProfile) {
                $this->error("Student profile not found for user {$userId}.");
                return 1;
            }
            
            $this->info("Using existing user: {$user->email} (ID: {$user->id})");
        } else {
            // Create a test user and student profile
            $user = User::factory()->create([
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
        }

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
            
            // Parse and validate structure
            $parsedJson = $roadmapService->parseRoadmapToStructuredJson($result['data']);
            $validation = $roadmapService->validateParsedStructure($parsedJson);
            
            $this->info("\n=== ROADMAP STRUCTURE ANALYSIS ===");
            $this->info('Total steps parsed: ' . count($parsedJson['steps'] ?? []));
            
            if (!$validation['valid']) {
                $this->error('Validation errors found:');
                foreach ($validation['errors'] as $error) {
                    $this->error('  - ' . $error);
                }
            } else {
                $this->info('✓ Structure validation passed');
            }
            
            // Display step details
            foreach ($parsedJson['steps'] ?? [] as $index => $step) {
                $stepNum = $index + 1;
                $this->info("\nStep {$stepNum}: " . ($step['heading'] ?? $step['duration'] ?? 'Unknown'));
                $this->info('  Topics count: ' . count($step['topics'] ?? []));
                $this->info('  Tools count: ' . count($step['tools'] ?? []));
                $this->info('  Skills count: ' . count($step['skills'] ?? []));
                $this->info('  Tasks count: ' . count($step['tasks'] ?? []));
                
                // Check topics count
                $topicsCount = count($step['topics'] ?? []);
                if ($topicsCount === 6) {
                    $this->info('  ✓ Topics: Exactly 6 (correct)');
                } else {
                    $this->error("  ✗ Topics: Expected 6, found {$topicsCount}");
                }
            }
            
            // Ask user if they want to see the complete roadmap
            if ($this->confirm('Do you want to see the complete generated roadmap?')) {
                $this->info('=== COMPLETE AI-GENERATED ROADMAP ===');
                $this->line($result['data']);
                $this->info('=== END OF ROADMAP ===');
            } else {
                // Display a portion of the generated roadmap
                $this->info("\nGenerated roadmap preview:");
                $this->line(substr($result['data'], 0, 500) . '...');
            }
        } else {
            $this->error('Failed to generate roadmap: ' . $result['message']);
            if (isset($result['admin_review_required']) && $result['admin_review_required']) {
                $this->warn('⚠ Admin review required - check roadmap_errors table');
            }
        }

        $this->info("\nTest completed. Check storage/logs/laravel.log for detailed logs.");
        return 0;
    }
}