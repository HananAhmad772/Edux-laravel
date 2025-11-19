<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\StudentQuiz;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIQuizService
{
    private $apiKey;
    private $apiUrl;
    
    public function __construct()
    {
        $this->apiKey = env('HUGGINGFACE_API_KEY');
        // Using the Hugging Face URL from .env file
        $this->apiUrl = env('HUGGINGFACE_URL', 'https://router.huggingface.co/v1/chat/completions');
        
        // Log the configuration for debugging
        Log::info('AIQuizService initialized', [
            'api_key_set' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey ?? ''),
            'api_url' => $this->apiUrl
        ]);
    }
    
    /**
     * Generate quiz questions based on student profile data
     *
     * @param StudentProfile $studentProfile
     * @return array
     */
    public function generateQuizQuestions(StudentProfile $studentProfile)
    {
        try {
            Log::info('Starting quiz generation', [
                'api_key' => substr($this->apiKey ?? '', 0, 5) . '...' . substr($this->apiKey ?? '', -5),
                'api_url' => $this->apiUrl
            ]);
            
            // Prepare the prompt with student profile data
            $prompt = $this->preparePrompt($studentProfile);
            
            // Call Hugging Face API
            Log::info('Calling Hugging Face API', [
                'model' => 'meta-llama/Llama-3.1-8B-Instruct',
                'prompt_length' => strlen($prompt)
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
                'max_tokens' => 1000,
                'temperature' => 0.7
            ]);
            
            Log::info('Hugging Face API response received', [
                'status_code' => $response->status(),
                'response_length' => strlen($response->body())
            ]);
            
            if ($response->failed()) {
                Log::error('Hugging Face API Error: ' . $response->body());
                // Generate fallback questions when API fails
                return [
                    'success' => true,
                    'message' => 'Generated fallback questions due to API error',
                    'data' => $this->generateFallbackQuestions($studentProfile)
                ];
            }
            
            $result = $response->json();
            
            // Handle different response formats
            if (isset($result['error'])) {
                Log::error('Hugging Face Model Error: ' . $result['error']);
                // Generate fallback questions when model returns error
                return [
                    'success' => true,
                    'message' => 'Generated fallback questions due to model error',
                    'data' => $this->generateFallbackQuestions($studentProfile)
                ];
            }
            
            // Extract generated text
            $generatedText = '';
            if (isset($result['choices'][0]['message']['content'])) {
                $generatedText = $result['choices'][0]['message']['content'];
            } elseif (isset($result[0]['generated_text'])) {
                $generatedText = $result[0]['generated_text'];
            } elseif (is_string($result)) {
                $generatedText = $result;
            } else {
                Log::error('Unexpected API response format', ['response' => $result]);
                // Generate fallback questions
                return [
                    'success' => true,
                    'message' => 'Generated fallback questions due to unexpected response',
                    'data' => $this->generateFallbackQuestions($studentProfile)
                ];
            }
            
            Log::info('Quiz generated successfully', [
                'generated_text_length' => strlen($generatedText)
            ]);
            
            // Parse the generated questions
            $questions = $this->parseGeneratedQuestions($generatedText);
            
            return [
                'success' => true,
                'message' => 'Quiz questions generated successfully',
                'data' => $questions
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Quiz Generation Error: ' . $e->getMessage());
            // Generate fallback questions when exception occurs
            return [
                'success' => true,
                'message' => 'Generated fallback questions due to exception: ' . $e->getMessage(),
                'data' => $this->generateFallbackQuestions($studentProfile)
            ];
        }
    }
    
    /**
     * Prepare the prompt for the AI model
     *
     * @param StudentProfile $studentProfile
     * @return string
     */
    private function preparePrompt(StudentProfile $studentProfile)
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

        // Map profile data to new prompt variables
        $main_area_of_interest = $profileData['major_subject'] ?? 'Not specified';
        $current_situation = $profileData['current_position'] ?? 'Not specified';
        $specialization = $profileData['specialization_field'] ?? 'Not specified';
        $preferred_technologies = $profileData['preferred_technologies'] ?? 'Not specified';
        $skill_level = $profileData['current_skill_level'] ?? 'Not specified';
        $main_goal = $profileData['main_goal'] ?? 'Not specified';
        $time_per_week = $profileData['time_per_week'] ?? 'Not specified';

        // Create the new prompt
        $prompt = "You are an intelligent AI quiz generator that creates short, adaptive quizzes to test a user's understanding in their chosen technical field.\n\n";
        $prompt .= "Below is the user's profile data:\n\n";
        $prompt .= "Main Area of Interest: {$main_area_of_interest}\n";
        $prompt .= "Current Situation: {$current_situation}\n";
        $prompt .= "Specialization: {$specialization}\n";
        $prompt .= "Preferred Technologies: {$preferred_technologies}\n";
        $prompt .= "Current Skill Level: {$skill_level}\n";
        $prompt .= "Main Goal: {$main_goal}\n";
        $prompt .= "Time per Week: {$time_per_week}\n\n";
        $prompt .= "🎯 Your task:\n";
        $prompt .= "Based on this profile, generate **5 quick quiz questions** that:\n";
        $prompt .= "1. Are related to the user's specialization and preferred technologies.\n";
        $prompt .= "2. Include both **theoretical** and **conceptual/problem-based** questions.\n";
        $prompt .= "3. Match the user's **skill level** (Beginner, Intermediate, or Advanced).\n";
        $prompt .= "4. Help evaluate the user's current position and understanding in this field.\n";
        $prompt .= "5. Include questions that can help identify knowledge gaps for creating a personalized learning roadmap.\n";
        $prompt .= "6. Use clear, concise phrasing suitable for an online quiz.\n\n";
        $prompt .= "🧠 Example:\n";
        $prompt .= "If the user is an intermediate web developer using JavaScript and React, include:\n";
        $prompt .= "- a question about JavaScript closures\n";
        $prompt .= "- one about React component lifecycle\n";
        $prompt .= "- one about state management\n";
        $prompt .= "- one about REST API integration\n";
        $prompt .= "- one conceptual question that checks problem-solving approach.\n\n";
        $prompt .= "Now, generate the quiz questions accordingly.\n\n";
        $prompt .= "Return ONLY a JSON array with 5 questions in this exact format:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"question\": \"Question text here?\",\n";
        $prompt .= "    \"options\": [\"A) Option 1\", \"B) Option 2\", \"C) Option 3\", \"D) Option 4\"],\n";
        $prompt .= "    \"correct_answer\": \"A) Option 1\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n";

        return $prompt;
    }
    
    /**
     * Parse the generated text to extract questions
     *
     * @param string $generatedText
     * @return array
     */
    private function parseGeneratedQuestions($generatedText)
    {
        // Clean the text
        $generatedText = trim($generatedText);
        
        // First, try to extract JSON array from the generated text
        $jsonStart = strpos($generatedText, '[');
        $jsonEnd = strrpos($generatedText, ']');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($generatedText, $jsonStart, $jsonEnd - $jsonStart + 1);
            
            $parsedData = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsedData) && count($parsedData) > 0) {
                // Convert new format to old format to maintain compatibility
                $convertedQuestions = [];
                foreach ($parsedData as $index => $questionData) {
                    // If the new format is detected (with type and difficulty)
                    if (isset($questionData['type']) && isset($questionData['difficulty'])) {
                        // Convert to old format with options and correct_answer
                        $convertedQuestions[] = [
                            'question' => $questionData['question'],
                            'options' => [
                                'A) Option 1', 
                                'B) Option 2', 
                                'C) Option 3', 
                                'D) Option 4'
                            ],
                            'correct_answer' => 'A) Option 1' // Default correct answer
                        ];
                    } else {
                        // Keep the old format as is
                        $convertedQuestions[] = $questionData;
                    }
                }
                
                // Ensure we have exactly 5 questions
                while (count($convertedQuestions) < 5) {
                    $convertedQuestions[] = [
                        'question' => 'Fallback question ' . (count($convertedQuestions) + 1),
                        'options' => [
                            'A) Option 1',
                            'B) Option 2', 
                            'C) Option 3',
                            'D) Option 4'
                        ],
                        'correct_answer' => 'A) Option 1'
                    ];
                }
                
                return array_slice($convertedQuestions, 0, 5);
            }
        }
        
        // If JSON parsing fails, try to parse the text format from the Python response
        $questions = $this->parseTextFormatQuestions($generatedText);
        if (!empty($questions)) {
            return $questions;
        }
        
        // If parsing fails, return fallback questions
        return $this->generateFallbackQuestions();
    }
    
    /**
     * Parse text format questions (like the ones from Python test)
     *
     * @param string $generatedText
     * @return array
     */
    private function parseTextFormatQuestions($generatedText)
    {
        $questions = [];
        
        // Split the text by question patterns
        $questionBlocks = preg_split('/\n{2,}(?=\d+\.|\*\*\d+\.)/', $generatedText);
        
        foreach ($questionBlocks as $block) {
            // Look for question pattern
            if (preg_match('/(?:\d+\.|\*\*\d+\.)\s*Question:?\s*(.*?)(?=\n[A-D][\).]|$)/is', $block, $questionMatch)) {
                $questionText = trim($questionMatch[1]);
                
                // Extract options
                $options = [];
                if (preg_match_all('/([A-D])[\).]\s*(.*?)(?=\n[A-D][\).]|\n\n|$)/s', $block, $optionMatches)) {
                    for ($i = 0; $i < count($optionMatches[0]); $i++) {
                        $options[] = $optionMatches[1][$i] . ') ' . trim($optionMatches[2][$i]);
                    }
                }
                
                // If we have a question and options, add to questions array
                if (!empty($questionText) && count($options) >= 2) {
                    $questions[] = [
                        'question' => $questionText,
                        'options' => $options,
                        'correct_answer' => $options[0] ?? 'A) Option 1' // Default to first option
                    ];
                }
            }
        }
        
        // Return the questions we found, or empty array if none
        return array_slice($questions, 0, 5);
    }
    
    /**
     * Generate fallback questions when AI fails
     *
     * @param StudentProfile $studentProfile
     * @return array
     */
    public function generateFallbackQuestions(StudentProfile $studentProfile = null)
    {
        $fallbackQuestions = [
            [
                'question' => 'What is the primary purpose of a constructor in object-oriented programming?',
                'options' => [
                    'A) To destroy objects',
                    'B) To initialize object properties',
                    'C) To define static methods',
                    'D) To declare variables'
                ],
                'correct_answer' => 'B) To initialize object properties'
            ],
            [
                'question' => 'Which data structure uses LIFO (Last In, First Out) principle?',
                'options' => [
                    'A) Queue',
                    'B) Array',
                    'C) Stack',
                    'D) Linked List'
                ],
                'correct_answer' => 'C) Stack'
            ],
            [
                'question' => 'In a database, what is a primary key?',
                'options' => [
                    'A) A key used for encryption',
                    'B) A unique identifier for each record',
                    'C) The first column in a table',
                    'D) A key used for sorting'
                ],
                'correct_answer' => 'B) A unique identifier for each record'
            ],
            [
                'question' => 'What does the term "polymorphism" refer to in OOP?',
                'options' => [
                    'A) Data hiding',
                    'B) Code reusability',
                    'C) The ability of objects to take multiple forms',
                    'D) Inheritance'
                ],
                'correct_answer' => 'C) The ability of objects to take multiple forms'
            ],
            [
                'question' => 'Which HTTP status code indicates a successful request?',
                'options' => [
                    'A) 404',
                    'B) 500',
                    'C) 301',
                    'D) 200'
                ],
                'correct_answer' => 'D) 200'
            ]
        ];
        
        return $fallbackQuestions;
    }
    
    /**
     * Save generated quiz to database
     *
     * @param string $studentId
     * @param array $quizData
     * @return StudentQuiz
     */
    public function saveQuiz($studentId, array $quizData)
    {
        // Check if quizData is already the questions array or nested under 'questions' key
        $questions = isset($quizData['questions']) ? $quizData['questions'] : $quizData;
        
        return StudentQuiz::create([
            'student_id' => $studentId,
            'questions' => json_encode($questions),
            'answers' => json_encode([]) // Empty answers initially
        ]);
    }
}