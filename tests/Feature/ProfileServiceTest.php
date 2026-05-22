<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ProfileService;
use App\Repositories\ProfileRepository;
use App\Services\AIQuizService;
use App\Services\AIRoadmapService;
use App\Services\AIDailyChallengeService;

class ProfileServiceTest extends TestCase
{
    /**
     * Test that the service can parse roadmap content correctly
     *
     * @return void
     */
    public function test_parse_roadmap_content_returns_six_topics_per_step()
    {
        // Create mock dependencies
        $userRepository = $this->createMock(\App\Repositories\UserRepository::class);
        $profileRepository = $this->createMock(ProfileRepository::class);
        $aiQuizService = $this->createMock(AIQuizService::class);
        $aiRoadmapService = $this->createMock(AIRoadmapService::class);
        $aiDailyChallengeService = $this->createMock(AIDailyChallengeService::class);
        
        // Create the service
        $service = new ProfileService(
            $userRepository,
            $profileRepository,
            $aiQuizService,
            $aiRoadmapService,
            $aiDailyChallengeService
        );
        
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
            * Implement a to-do list
            
**Week 3–4: Intermediate Concepts**

        - Topics to study
            * JavaScript ES6 features
            * Async programming
            * API integration
            * React components
            * State management
            * Routing
            
        - Tools to use
            * React Developer Tools
            * Postman
            * Webpack
            
        - Skills learned
            * Building dynamic web applications
            * Working with APIs
            * Component-based architecture
            
        - Mini practice tasks or micro-projects
            * Build a weather app
            * Create a task manager
            * Implement user authentication";
            
        // Invoke the private method using reflection
        $reflection = new \ReflectionClass(get_class($service));
        $method = $reflection->getMethod('parseRoadmapContent');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($service, [$testContent]);
        
        // Verify we have the correct number of steps
        $this->assertCount(2, $result);
        
        // Verify each step has exactly 6 topics
        foreach ($result as $step) {
            $this->assertCount(6, $step['topics'], "Each step should have exactly 6 topics");
        }
    }
    
    /**
     * Test that the service fixes steps with fewer than 6 topics
     *
     * @return void
     */
    public function test_parse_roadmap_content_fixes_fewer_than_six_topics()
    {
        // Create mock dependencies
        $userRepository = $this->createMock(\App\Repositories\UserRepository::class);
        $profileRepository = $this->createMock(ProfileRepository::class);
        $aiQuizService = $this->createMock(AIQuizService::class);
        $aiRoadmapService = $this->createMock(AIRoadmapService::class);
        $aiDailyChallengeService = $this->createMock(AIDailyChallengeService::class);
        
        // Create the service
        $service = new ProfileService(
            $userRepository,
            $profileRepository,
            $aiQuizService,
            $aiRoadmapService,
            $aiDailyChallengeService
        );
        
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
            
        // Invoke the private method using reflection
        $reflection = new \ReflectionClass(get_class($service));
        $method = $reflection->getMethod('parseRoadmapContent');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($service, [$testContent]);
        
        // Verify we have the correct number of steps
        $this->assertCount(1, $result);
        
        // Verify the step has exactly 6 topics after fixing
        $this->assertCount(6, $result['Week 1–2']['topics'], "Step should have exactly 6 topics after fixing");
        
        // Verify that the missing topics were filled with placeholders
        $this->assertEquals("Topic 4 placeholder", $result['Week 1–2']['topics'][3]);
        $this->assertEquals("Topic 5 placeholder", $result['Week 1–2']['topics'][4]);
        $this->assertEquals("Topic 6 placeholder", $result['Week 1–2']['topics'][5]);
    }
}