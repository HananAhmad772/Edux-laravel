<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\StudentRoadmap;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIRoadmapService
{
    private $apiKey;
    private $apiUrl;
    
    public function __construct()
    {
        $this->apiKey = env('HUGGINGFACE_API_KEY');
        $this->apiUrl = env('HUGGINGFACE_URL', 'https://router.huggingface.co/v1/chat/completions');
    }
    
    /**
     * Generate personalized learning roadmap based on student profile and quiz data
     *
     * @param StudentProfile $studentProfile
     * @param array $quizData
     * @return array
     */
    public function generateLearningRoadmap(StudentProfile $studentProfile, array $quizData)
    {
        try {
            Log::info('Starting roadmap generation for student ID: ' . $studentProfile->user_id);
            
            // Prepare the prompt with student profile data and quiz results
            $prompt = $this->prepareRoadmapPrompt($studentProfile, $quizData);
            
            Log::info('Roadmap prompt prepared', [
                'student_id' => $studentProfile->user_id,
                'prompt_length' => strlen($prompt)
            ]);
            
            // Call Hugging Face API
            Log::info('Calling Hugging Face API', [
                'student_id' => $studentProfile->user_id,
                'api_url' => $this->apiUrl
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => 'meta-llama/Llama-3.1-8B-Instruct',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);
            
            Log::info('Hugging Face API response received', [
                'student_id' => $studentProfile->user_id,
                'status_code' => $response->status(),
                'response_length' => strlen($response->body())
            ]);
            
            if ($response->failed()) {
                Log::error('Hugging Face API Error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Failed to generate roadmap due to API error',
                    'data' => null
                ];
            }
            
            $result = $response->json();
            
            Log::info('Hugging Face API response parsed', [
                'student_id' => $studentProfile->user_id,
                'result_keys' => array_keys($result ?? [])
            ]);
            
            // Handle different response formats
            if (isset($result['error'])) {
                Log::error('Hugging Face Model Error: ' . $result['error']);
                return [
                    'success' => false,
                    'message' => 'Failed to generate roadmap due to model error',
                    'data' => null
                ];
            }
            
            // Extract generated text
            $generatedText = '';
            if (isset($result['choices'][0]['message']['content'])) {
                $generatedText = $result['choices'][0]['message']['content'];
                Log::info('Roadmap generated successfully', [
                    'student_id' => $studentProfile->user_id,
                    'generated_text_length' => strlen($generatedText)
                ]);
            } else {
                Log::error('Unexpected API response format', ['response' => $result]);
                return [
                    'success' => false,
                    'message' => 'Failed to generate roadmap due to unexpected response format',
                    'data' => null
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Learning roadmap generated successfully',
                'data' => $generatedText
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Roadmap Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate roadmap due to exception: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Prepare the prompt for the AI model to generate a learning roadmap
     *
     * @param StudentProfile $studentProfile
     * @param array $quizData
     * @return string
     */
    private function prepareRoadmapPrompt(StudentProfile $studentProfile, array $quizData)
    {
        // Extract profile data
        $profileData = [
            'major_subject' => $studentProfile->major_subject,
            'current_position' => $studentProfile->current_position,
            'specialization_field' => $studentProfile->specialization_field,
            'preferred_technologies' => $studentProfile->preferred_technologies,
            'current_skill_level' => $studentProfile->current_skill_level,
            'main_goal' => $studentProfile->main_goal,
            'time_per_week' => $studentProfile->time_per_week,
        ];

        // Create the prompt
        $prompt = "You are an expert learning path advisor that creates personalized learning roadmaps for students based on their profile and quiz performance.\n\n";
        $prompt .= "Student Profile:\n";
        $prompt .= "- Major Subject: " . ($profileData['major_subject'] ?? 'Not specified') . "\n";
        $prompt .= "- Current Position: " . ($profileData['current_position'] ?? 'Not specified') . "\n";
        $prompt .= "- Specialization Field: " . ($profileData['specialization_field'] ?? 'Not specified') . "\n";
        $prompt .= "- Preferred Technologies: " . ($profileData['preferred_technologies'] ?? 'Not specified') . "\n";
        $prompt .= "- Current Skill Level: " . ($profileData['current_skill_level'] ?? 'Not specified') . "\n";
        $prompt .= "- Main Goal: " . ($profileData['main_goal'] ?? 'Not specified') . "\n";
        $prompt .= "- Time Available Per Week: " . ($profileData['time_per_week'] ?? 'Not specified') . "\n\n";
        
        // Add quiz data if available
        if (!empty($quizData)) {
            $prompt .= "Quiz Performance:\n";
            foreach ($quizData as $quiz) {
                $prompt .= "- Quiz ID: " . ($quiz['id'] ?? 'N/A') . "\n";
                $prompt .= "- Score: " . ($quiz['score'] ?? 'N/A') . "%\n";
                // Add more quiz details if needed
            }
            $prompt .= "\n";
        }
        
        $prompt .= "🎯 Your task:\n";
        $prompt .= "Create a comprehensive, personalized learning roadmap that:\n";
        $prompt .= "1. Analyzes the student's current skill level based on their profile and quiz performance\n";
        $prompt .= "2. Identifies knowledge gaps from the quiz results\n";
        $prompt .= "3. Provides a step-by-step learning path from their current level to their goal\n";
        $prompt .= "4. Recommends specific resources, courses, or topics to study\n";
        $prompt .= "5. Suggests a realistic timeline based on their available time per week\n";
        $prompt .= "6. Includes milestones and checkpoints to track progress\n\n";
        
        $prompt .= "📝 Please format your response as a structured learning roadmap with:\n";
        $prompt .= "- Current Skill Assessment\n";
        $prompt .= "- Learning Objectives\n";
        $prompt .= "- Phase-by-Phase Plan (Beginner → Intermediate → Advanced)\n";
        $prompt .= "- Recommended Resources\n";
        $prompt .= "- Timeline and Milestones\n";
        $prompt .= "- Progress Tracking Methods\n\n";
        
        $prompt .= "Ensure the roadmap is detailed, actionable, and tailored specifically to this student's profile and goals.";

        return $prompt;
    }
    
    /**
     * Save generated roadmap to database
     *
     * @param string $studentId
     * @param string $roadmapContent
     * @return StudentRoadmap
     */
    public function saveRoadmap($studentId, string $roadmapContent)
    {
        Log::info('Saving roadmap to database', [
            'student_id' => $studentId,
            'roadmap_content_length' => strlen($roadmapContent)
        ]);
        
        $roadmap = StudentRoadmap::create([
            'student_id' => $studentId,
            'roadmap_content' => $roadmapContent
        ]);
        
        Log::info('Roadmap saved successfully', [
            'student_id' => $studentId,
            'roadmap_id' => $roadmap->id
        ]);
        
        return $roadmap;
    }
}