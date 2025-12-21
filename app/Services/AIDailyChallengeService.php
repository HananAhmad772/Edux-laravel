<?php

namespace App\Services;

use App\Models\DailyChallenge;
use App\Models\StudentProfile;
use App\Models\StudentRoadmap;
use App\Models\UserProgress;
use App\Repositories\ProfileRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDailyChallengeService
{
    private $apiKey;
    private $apiUrl;
    private $model;
    private $profileRepository;
    
    public function __construct(ProfileRepository $profileRepository)
    {
        $this->apiKey = env('HUGGINGFACE_API_KEY');
        $this->apiUrl = env('HUGGINGFACE_URL', 'https://router.huggingface.co/v1/chat/completions');
        $this->model = 'meta-llama/Llama-3.1-8B-Instruct';
        $this->profileRepository = $profileRepository;
    }
    
    /**
     * Generate a daily challenge based on the student's current topic
     *
     * @param string $userId
     * @return array
     */
    public function generateDailyChallenge($userId)
    {
        try {
            // Get student profile
            $studentProfile = StudentProfile::where('user_id', $userId)->first();
            if (!$studentProfile) {
                return [
                    'success' => false,
                    'message' => 'Student profile not found',
                    'data' => null
                ];
            }
            
            // Get or create user progress
            $userProgress = $this->profileRepository->getOrCreateUserProgress($userId);
            
            // Get latest roadmap
            $roadmap = StudentRoadmap::where('student_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$roadmap) {
                return [
                    'success' => false,
                    'message' => 'No roadmap found for this student',
                    'data' => null
                ];
            }
            
            // Update user progress with roadmap ID if not already set
            if (!$userProgress->roadmap_id) {
                $userProgress->roadmap_id = $roadmap->id;
                $userProgress->save();
            }
            
            // Parse roadmap JSON
            $roadmapJson = $roadmap->roadmap_json ?? [];
            
            // If roadmap_json is a string, decode it
            if (is_string($roadmapJson)) {
                $roadmapJson = json_decode($roadmapJson, true);
            }
            
            $steps = $roadmapJson['steps'] ?? [];
            
            if (empty($steps)) {
                return [
                    'success' => false,
                    'message' => 'Invalid roadmap structure',
                    'data' => null
                ];
            }
            
            // Find current step and topic
            $currentStep = null;
            $currentTopic = null;
            
            foreach ($steps as $step) {
                if (($step['duration'] ?? '') === $userProgress->current_step) {
                    $currentStep = $step;
                    $topics = $step['topics'] ?? [];
                    // Get the current topic based on topic index (1-based)
                    $currentTopic = $topics[$userProgress->current_topic_index - 1] ?? null;
                    break;
                }
            }
            
            // If we couldn't find the current step, use the first one
            if (!$currentStep) {
                $currentStep = $steps[0] ?? null;
                $topics = $currentStep['topics'] ?? [];
                $currentTopic = $topics[0] ?? null;
                
                // Update user progress to match the first step
                if ($currentStep) {
                    $userProgress->current_step = $currentStep['duration'] ?? '';
                    $userProgress->current_topic_index = 1;
                    $userProgress->save();
                }
            }
            
            if (!$currentStep || !$currentTopic) {
                return [
                    'success' => false,
                    'message' => 'Could not determine current topic',
                    'data' => null
                ];
            }
            
            // Check if a challenge already exists for today
            $today = now()->toDateString();
            $existingChallenge = DailyChallenge::where('user_id', $userId)
                ->where('challenge_date', $today)
                ->first();
            
            if ($existingChallenge && $existingChallenge->challenge_data) {
                // Ensure the challenge data is in the correct format
                $challengeData = $existingChallenge->challenge_data;
                
                // If challenge_data is a string, try to parse it as JSON
                if (is_string($challengeData)) {
                    $parsed = json_decode($challengeData, true);
                    if (is_array($parsed)) {
                        $challengeData = $parsed;
                    }
                }
                
                // Check if we need to reformat the data
                if (isset($challengeData['description']) && strpos($challengeData['description'], '{') === 0) {
                    // The data is in the old format, reformat it
                    $challengeData = $this->reformatChallengeData($challengeData);
                    
                    // Update the database with the reformatted data
                    $existingChallenge->update([
                        'challenge_description' => $challengeData['description'] ?? '',
                        'challenge_data' => $challengeData
                    ]);
                } elseif (!isset($challengeData['instructions']) || !isset($challengeData['expected_outcome'])) {
                    // If the data doesn't have the expected structure, reformat it
                    $challengeData = $this->reformatChallengeData($challengeData);
                    
                    // Update the database with the reformatted data
                    $existingChallenge->update([
                        'challenge_description' => $challengeData['description'] ?? '',
                        'challenge_data' => $challengeData
                    ]);
                }
                
                return [
                    'success' => true,
                    'message' => 'Daily challenge retrieved successfully',
                    'data' => $existingChallenge
                ];
            }
            
            // Prepare the prompt for AI challenge generation
            $prompt = $this->prepareChallengePrompt($studentProfile, $currentStep, $currentTopic);
            
            // Call Hugging Face API to generate challenge
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7
            ]);
            
            if ($response->failed()) {
                Log::error('AI Daily Challenge Generation Error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Failed to generate daily challenge',
                    'data' => null
                ];
            }
            
            $result = $response->json();
            
            if (isset($result['error'])) {
                Log::error('AI Daily Challenge Model Error: ' . $result['error']);
                return [
                    'success' => false,
                    'message' => 'AI model returned an error',
                    'data' => null
                ];
            }
            
            $generatedText = '';
            if (isset($result['choices'][0]['message']['content'])) {
                $generatedText = $result['choices'][0]['message']['content'];
            } else {
                Log::error('AI Daily Challenge: Unexpected API response format', ['response' => $result]);
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from AI model',
                    'data' => null
                ];
            }
            
            // Parse the generated challenge
            $challengeData = $this->parseGeneratedChallenge($generatedText);
            
            // Create or update the daily challenge record
            $challenge = DailyChallenge::updateOrCreate(
                [
                    'user_id' => $userId,
                    'challenge_date' => $today
                ],
                [
                    'roadmap_id' => $roadmap->id,
                    'step_name' => $currentStep['duration'] ?? null,
                    'topic_index' => $userProgress->current_topic_index,
                    'topic_name' => $currentTopic,
                    'challenge_description' => $challengeData['description'] ?? '',
                    'challenge_data' => $challengeData
                ]
            );
            
            return [
                'success' => true,
                'message' => 'Daily challenge generated successfully',
                'data' => $challenge
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Daily Challenge Generation Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while generating the daily challenge: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Evaluate student's submission and provide AI feedback
     *
     * @param string $challengeId
     * @param string $submission
     * @return array
     */
    public function evaluateSubmission($challengeId, $submission)
    {
        try {
            // Get the challenge
            $challenge = DailyChallenge::find($challengeId);
            if (!$challenge) {
                return [
                    'success' => false,
                    'message' => 'Challenge not found',
                    'data' => null
                ];
            }
            
            // Get student profile
            $studentProfile = StudentProfile::where('user_id', $challenge->user_id)->first();
            if (!$studentProfile) {
                return [
                    'success' => false,
                    'message' => 'Student profile not found',
                    'data' => null
                ];
            }
            
            // Prepare the prompt for AI evaluation
            $prompt = $this->prepareEvaluationPrompt($studentProfile, $challenge, $submission);
            
            // Call Hugging Face API to evaluate submission
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3 // Lower temperature for more consistent evaluation
            ]);
            
            if ($response->failed()) {
                Log::error('AI Submission Evaluation Error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Failed to evaluate submission',
                    'data' => null
                ];
            }
            
            $result = $response->json();
            
            if (isset($result['error'])) {
                Log::error('AI Submission Evaluation Model Error: ' . $result['error']);
                return [
                    'success' => false,
                    'message' => 'AI model returned an error',
                    'data' => null
                ];
            }
            
            $generatedText = '';
            if (isset($result['choices'][0]['message']['content'])) {
                $generatedText = $result['choices'][0]['message']['content'];
            } else {
                Log::error('AI Submission Evaluation: Unexpected API response format', ['response' => $result]);
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from AI model',
                    'data' => null
                ];
            }
            
            // Update the challenge with submission and feedback
            $challenge->update([
                'student_submission' => $submission,
                'ai_feedback' => $generatedText,
                'is_completed' => true,
                'points_earned' => $this->calculatePoints($generatedText)
            ]);
            
            return [
                'success' => true,
                'message' => 'Submission evaluated successfully',
                'data' => $challenge
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Submission Evaluation Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while evaluating the submission: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Prepare the prompt for AI challenge generation
     *
     * @param StudentProfile $studentProfile
     * @param array $currentStep
     * @param string $currentTopic
     * @return string
     */
private function prepareChallengePrompt(StudentProfile $studentProfile, array $currentStep, string $currentTopic)
{
    $profileData = [
        'major_subject' => $studentProfile->major_subject ?? 'Not specified',
        'current_position' => $studentProfile->current_position ?? 'Not specified',
        'specialization_field' => $studentProfile->specialization_field ?? 'Not specified',
        'preferred_technologies' => $studentProfile->preferred_technologies ?? 'Not specified',
        'current_skill_level' => $studentProfile->current_skill_level ?? 'Not specified',
        'main_goal' => $studentProfile->main_goal ?? 'Not specified',
    ];
    
    $prompt = "You are an expert educator and AI assistant that creates engaging daily coding/learning challenges for students.\n\n";
    $prompt .= "STUDENT PROFILE:\n";
    $prompt .= "- Major Subject: {$profileData['major_subject']}\n";
    $prompt .= "- Current Position: {$profileData['current_position']}\n";
    $prompt .= "- Specialization Field: {$profileData['specialization_field']}\n";
    $prompt .= "- Preferred Technologies: {$profileData['preferred_technologies']}\n";
    $prompt .= "- Current Skill Level: {$profileData['current_skill_level']}\n";
    $prompt .= "- Main Goal: {$profileData['main_goal']}\n\n";
    
    $prompt .= "CURRENT LEARNING TOPIC:\n";
    $prompt .= "- Step: {$currentStep['duration']}\n";
    $prompt .= "- Title: {$currentStep['title']}\n";
    $prompt .= "- Topic: $currentTopic\n\n";
    
    $prompt .= "YOUR TASK:\n";
    $prompt .= "Create an engaging daily challenge for this student based on their current learning topic.\n\n";
    
    $prompt .= "CHALLENGE REQUIREMENTS:\n";
    $prompt .= "1. Must be relevant to the current topic\n";
    $prompt .= "2. Should match the student's skill level\n";
    $prompt .= "3. Must be completable in 15-30 minutes\n";
    $prompt .= "4. Should encourage hands-on practice\n";
    $prompt .= "5. Must include clear instructions\n";
    $prompt .= "6. Should have a specific goal/outcome\n\n";

    $prompt .= "INCLUDE SOLUTION:\n";
    $prompt .= "Also provide a concise solution or example answer that the student can refer to if they get stuck.\n\n";
    
    $prompt .= "RETURN FORMAT:\n";
    $prompt .= "Provide your response in the following JSON format:\n";
    $prompt .= "{\n";
    $prompt .= "  \"description\": \"A brief description of the challenge (1-2 sentences)\",\n";
    $prompt .= "  \"instructions\": \"Clear step-by-step instructions for completing the challenge\",\n";
    $prompt .= "  \"expected_outcome\": \"What the student should achieve by completing this challenge\",\n";
    $prompt .= "  \"tips\": [\"Helpful tips or resources for completing the challenge\"],\n";
    $prompt .= "  \"solution\": \"Provide a working solution or guidance if the student is stuck\",\n";
    $prompt .= "  \"difficulty\": \"Beginner|Intermediate|Advanced\",\n";
    $prompt .= "  \"estimated_time\": \"15-30 minutes\"\n";
    $prompt .= "}\n\n";
    
    $prompt .= "IMPORTANT: Return ONLY the JSON object, nothing else.\n";
    
    return $prompt;
}

    
    /**
     * Prepare the prompt for AI submission evaluation
     *
     * @param StudentProfile $studentProfile
     * @param DailyChallenge $challenge
     * @param string $submission
     * @return string
     */
    private function prepareEvaluationPrompt(StudentProfile $studentProfile, DailyChallenge $challenge, string $submission)
    {
        $profileData = [
            'major_subject' => $studentProfile->major_subject ?? 'Not specified',
            'current_skill_level' => $studentProfile->current_skill_level ?? 'Not specified',
        ];
        
        $challengeData = $challenge->challenge_data ?? [];
        
        $prompt = "You are an expert educator and AI assistant that evaluates student submissions for coding/learning challenges.\n\n";
        $prompt .= "STUDENT PROFILE:\n";
        $prompt .= "- Major Subject: {$profileData['major_subject']}\n";
        $prompt .= "- Current Skill Level: {$profileData['current_skill_level']}\n\n";
        
        $prompt .= "CHALLENGE DETAILS:\n";
        $prompt .= "- Topic: {$challenge->topic_name}\n";
        $prompt .= "- Description: {$challenge->challenge_description}\n";
        $prompt .= "- Instructions: " . ($challengeData['instructions'] ?? 'N/A') . "\n\n";
        
        $prompt .= "STUDENT SUBMISSION:\n";
        $prompt .= "$submission\n\n";
        
        $prompt .= "YOUR TASK:\n";
        $prompt .= "Evaluate the student's submission and provide constructive feedback.\n\n";
        
        $prompt .= "EVALUATION CRITERIA:\n";
        $prompt .= "1. Did they complete the challenge correctly?\n";
        $prompt .= "2. Quality of their solution\n";
        $prompt .= "3. Understanding of the concepts\n";
        $prompt .= "4. Areas for improvement\n";
        $prompt .= "5. Suggestions for further learning\n\n";
        
        $prompt .= "FEEDBACK REQUIREMENTS:\n";
        $prompt .= "1. Be encouraging and constructive\n";
        $prompt .= "2. Point out what they did well\n";
        $prompt .= "3. Identify specific areas for improvement\n";
        $prompt .= "4. Provide actionable suggestions\n";
        $prompt .= "5. Suggest next steps for learning\n\n";
        
        $prompt .= "RESPONSE FORMAT:\n";
        $prompt .= "Provide your feedback in a clear, structured format:\n";
        $prompt .= "## Evaluation Summary\n";
        $prompt .= "[Brief summary of overall performance]\n\n";
        $prompt .= "## What You Did Well\n";
        $prompt .= "- [Positive aspects of their submission]\n\n";
        $prompt .= "## Areas for Improvement\n";
        $prompt .= "- [Suggestions for improvement]\n\n";
        $prompt .= "## Next Steps\n";
        $prompt .= "[Recommendations for further learning]\n\n";
        $prompt .= "## Score\n";
        $prompt .= "[Score out of 100: XX/100]";
        
        return $prompt;
    }
    
    /**
     * Parse the generated challenge from AI response
     *
     * @param string $generatedText
     * @return array
     */
    private function parseGeneratedChallenge($generatedText)
    {
        // First, try to parse the entire response as JSON
        $parsed = $this->extractAndParseJson($generatedText);
        
        if (!$parsed) {
            // If that fails, try to extract JSON from within the response
            $jsonStart = strpos($generatedText, '{');
            $jsonEnd = strrpos($generatedText, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($generatedText, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = $this->extractAndParseJson($jsonString);
            }
        }
        
        // If we still don't have valid data, create a basic structure
        if (!$parsed) {
            $parsed = [
                'description' => $generatedText,
                'instructions' => 'Complete the challenge based on the description above.',
                'expected_outcome' => 'Demonstrate understanding of the topic.',
                'tips' => [],
                'difficulty' => 'Intermediate',
                'estimated_time' => '20 minutes'
            ];
        }
        
        // Reformat the data into the desired structure
        return $this->reformatChallengeData($parsed);
    }
    
    /**
     * Extract and parse JSON from a string
     *
     * @param string $text
     * @return array|null
     */
    private function extractAndParseJson($text)
    {
        if (!is_string($text)) {
            return null;
        }
        
        // Try to parse the text as JSON
        $parsed = json_decode($text, true);
        
        if (is_array($parsed)) {
            return $parsed;
        }
        
        return null;
    }
    
    /**
     * Reformat challenge data into the desired structure
     *
     * @param array $data
     * @return array
     */
    private function reformatChallengeData($data)
    {
        // Handle nested JSON in description field
        if (isset($data['description']) && is_string($data['description'])) {
            $nestedParsed = $this->extractAndParseJson($data['description']);
            if ($nestedParsed && is_array($nestedParsed)) {
                // Merge the nested data with the main data
                $data = array_merge($data, $nestedParsed);
            }
        }
        
        // Format the description
        $description = "Description:\n\n" . ($data['description'] ?? 'No description provided.');
        
        // Format the instructions
        $instructions = "Instructions:\n";
        if (isset($data['instructions'])) {
            if (is_array($data['instructions'])) {
                foreach ($data['instructions'] as $index => $instruction) {
                    $instructions .= ($index + 1) . ". " . $instruction . "\n";
                }
            } else {
                // If instructions is a string, try to split it into steps
                $instructionLines = explode("\n", $data['instructions']);
                $stepNumber = 1;
                foreach ($instructionLines as $line) {
                    $cleanLine = trim($line);
                    if (!empty($cleanLine)) {
                        // Check if the line already starts with a number
                        if (preg_match('/^\d+\./', $cleanLine)) {
                            $instructions .= $cleanLine . "\n";
                        } else {
                            $instructions .= $stepNumber . ". " . $cleanLine . "\n";
                            $stepNumber++;
                        }
                    }
                }
            }
        } else {
            $instructions .= "1. Complete the challenge based on the description above.\n";
        }
        
        // Format the expected outcome
        $expectedOutcome = "Expected outcome:\n" . ($data['expected_outcome'] ?? 'Demonstrate understanding of the topic.');
        
        // Format the tips
        $tips = "Tips:\n";
        if (isset($data['tips']) && is_array($data['tips'])) {
            foreach ($data['tips'] as $tip) {
                $tips .= "- " . $tip . "\n";
            }
        } else {
            $tips .= "- Review the relevant learning materials\n";
            $tips .= "- Ask for help if you're stuck\n";
        }
        
        return [
            'description' => $description,
            'instructions' => $instructions,
            'expected_outcome' => $expectedOutcome,
            'tips' => $tips,
            'difficulty' => $data['difficulty'] ?? 'Intermediate',
            'estimated_time' => $data['estimated_time'] ?? '20 minutes',
            'solution' => $data['solution'] ?? ''
        ];
    }
    
    /**
     * Calculate points based on AI feedback
     *
     * @param string $feedback
     * @return int
     */
    private function calculatePoints($feedback)
    {
        // Extract score from feedback if it exists
        if (preg_match('/(\d+)\/100/', $feedback, $matches)) {
            return (int)$matches[1];
        }
        
        // Default to 70 points if no score found
        return 70;
    }
}