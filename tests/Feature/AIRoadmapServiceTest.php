<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AIRoadmapService;
use App\Models\StudentProfile;

class AIRoadmapServiceTest extends TestCase
{
    /**
     * Test that the roadmap generator returns exactly 6 topics per step
     *
     * @return void
     */
    public function test_roadmap_generator_returns_six_topics_per_step()
    {
        // Create a mock student profile
        $studentProfile = new StudentProfile([
            'major_subject' => 'Web Development',
            'current_position' => 'Beginner',
            'specialization_field' => 'Frontend',
            'preferred_technologies' => 'JavaScript, React',
            'current_skill_level' => 'Beginner',
            'main_goal' => 'Build web applications',
            'time_per_week' => '10 hours'
        ]);
        
        // Create a mock quiz data
        $quizData = [
            [
                'id' => 1,
                'score' => 80
            ]
        ];
        
        // Create the service
        $service = new AIRoadmapService();
        
        // Since we can't actually call the AI API in tests, we'll test the validation function directly
        $testContent = "**Week 1–2: Foundations**
        
        - Topics to study
            * HTML basics
            * CSS fundamentals
            * JavaScript variables
            * DOM manipulation
            * Event handling
            * Functions and scope
            
        - Tools to use
            * Visual Studio Code
            * Chrome DevTools
            * Git
            
        - Skills learned
            * Building static web pages
            * Styling web pages
            * Adding interactivity
            
        - Mini practice tasks or micro-projects
            * Create a personal webpage
            * Build a photo gallery
            * Implement a to-do list";
            
        $result = $this->invokeMethod($service, 'validateAndFixRoadmapStructure', [$testContent]);
        
        $this->assertTrue($result['valid']);
        
        // Parse the content to verify it has 6 topics
        $steps = $this->parseRoadmapSteps($result['content']);
        
        foreach ($steps as $step) {
            $this->assertCount(6, $step['topics'], "Each step should have exactly 6 topics");
        }
    }
    
    /**
     * Test that the roadmap generator fixes steps with fewer than 6 topics
     *
     * @return void
     */
    public function test_roadmap_generator_fixes_fewer_than_six_topics()
    {
        // Create the service
        $service = new AIRoadmapService();
        
        $testContent = "**Week 1–2: Foundations**
        
        - Topics to study
            * HTML basics
            * CSS fundamentals
            * JavaScript variables
            
        - Tools to use
            * Visual Studio Code
            * Chrome DevTools
            
        - Skills learned
            * Building static web pages
            * Styling web pages
            
        - Mini practice tasks or micro-projects
            * Create a personal webpage
            * Build a photo gallery";
            
        $result = $this->invokeMethod($service, 'validateAndFixRoadmapStructure', [$testContent]);
        
        $this->assertTrue($result['valid']);
        
        // Parse the content to verify it now has 6 topics
        $steps = $this->parseRoadmapSteps($result['content']);
        
        foreach ($steps as $step) {
            $this->assertCount(6, $step['topics'], "Each step should have exactly 6 topics after fixing");
        }
    }
    
    /**
     * Helper method to invoke private methods for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
    
    /**
     * Helper method to parse roadmap steps
     */
    protected function parseRoadmapSteps($roadmapContent)
    {
        $steps = [];
        $lines = explode("\n", $roadmapContent);
        $currentStep = null;
        $currentSection = null;
        $stepPattern = '/^\*\*Week (\d+)–(\d+): (.+)\*\*$/';
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Check for step headings
            if (preg_match($stepPattern, $trimmedLine, $matches)) {
                $stepName = "Week {$matches[1]}–{$matches[2]}";
                $stepTitle = $matches[3];
                
                $currentStep = [
                    'name' => $stepName,
                    'title' => $stepTitle,
                    'topics' => [],
                    'tools' => [],
                    'skills' => [],
                    'tasks' => []
                ];
                
                $steps[$stepName] = $currentStep;
                $currentSection = null;
            } else if ($currentStep) {
                // Check for section headings
                if (strpos($trimmedLine, 'Topics to study') !== false) {
                    $currentSection = 'topics';
                } else if (strpos($trimmedLine, 'Tools to use') !== false) {
                    $currentSection = 'tools';
                } else if (strpos($trimmedLine, 'Skills learned') !== false) {
                    $currentSection = 'skills';
                } else if (strpos($trimmedLine, 'Mini practice tasks or micro-projects') !== false) {
                    $currentSection = 'tasks';
                } else if ($currentSection && preg_match('/^\s*\*\s*(.+)$/', $trimmedLine, $matches)) {
                    // Add item to current section
                    $steps[$currentStep['name']][$currentSection][] = trim($matches[1]);
                }
            }
        }
        
        return $steps;
    }
}