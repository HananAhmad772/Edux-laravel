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
    $profileData = [
        'major_subject' => $studentProfile->major_subject ?? 'Not specified',
        'current_position' => $studentProfile->current_position ?? 'Not specified',
        'specialization_field' => $studentProfile->specialization_field ?? 'Not specified',
        'preferred_technologies' => $studentProfile->preferred_technologies ?? 'Not specified',
        'current_skill_level' => $studentProfile->current_skill_level ?? 'Not specified',
        'main_goal' => $studentProfile->main_goal ?? 'Not specified',
        'time_per_week' => $studentProfile->time_per_week ?? 'Not specified',
    ];

    $prompt = "
You are an expert learning-path advisor. Generate a structured, personalized learning roadmap based on the student profile and quiz performance.

IMPORTANT FORMATTING RULES:
- Only the roadmap step headings (Week-based steps) must use **double asterisks**.
- No other headings or text may use double asterisks.
- Steps should be formatted like:
  **Week 1–2: Foundations**
  **Week 3–4: Backend Basics**

STUDENT PROFILE
- Major Subject: {$profileData['major_subject']}
- Current Position: {$profileData['current_position']}
- Specialization Field: {$profileData['specialization_field']}
- Preferred Technologies: {$profileData['preferred_technologies']}
- Current Skill Level: {$profileData['current_skill_level']}
- Main Goal: {$profileData['main_goal']}
- Time Available Per Week: {$profileData['time_per_week']}

QUIZ PERFORMANCE SUMMARY
";

    if (!empty($quizData)) {
        foreach ($quizData as $quiz) {
            $quizId = $quiz['id'] ?? 'Unknown';
            $score = $quiz['score'] ?? 'N/A';
            $prompt .= "- Quiz ID: {$quizId} | Score: {$score}%\n";
        }
    } else {
        $prompt .= "No quiz data available.\n";
    }

    $prompt .= "

YOUR TASK
Return the final result using the following sections in this exact order:

1. Roadmap Explanation:
   - A short explanation (4–5 lines) describing why this roadmap is important.

2. Key Highlights:
   - 4–5 bullet points summarizing what the student will achieve.

3. Current Skill Assessment:
   - Strengths
   - Weaknesses
   - Gaps detected from quiz performance

4. Learning Objectives:
   - Clear technical and career-aligned objectives.

5. Learning Roadmap (2–24 weeks):
   - Break the roadmap into weekly or multi-week steps.
   - ONLY step headings must use this format:
     **Week X–Y: Step Title**
   - Under each step (normal formatting, no bold):
     - Topics to study
     - Tools to use
     - Skills learned
     - Mini practice tasks or micro-projects

6. Recommended Resources:
   - Courses
   - Tutorials
   - Documentation
   - GitHub repos

7. Timeline & Milestones:
   - Define what the student should achieve at major checkpoints.

8. Progress Tracking Methods:
   - Weekly self-checks
   - Skill checklists
   - Portfolio-building plan

Ensure the output is clean, structured, and easy for frontend integration.
";

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