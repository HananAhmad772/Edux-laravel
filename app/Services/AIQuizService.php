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
        // Using a more suitable model for question generation
        $this->apiUrl = 'https://api-inference.huggingface.co/models/google/flan-t5-base';
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
            // Prepare the prompt with student profile data
            $prompt = $this->preparePrompt($studentProfile);
            
            // Call Hugging Face API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(50)->post($this->apiUrl, [
                'inputs' => $prompt,
                'parameters' => [
                    'max_new_tokens' => 800,
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'return_full_text' => false,
                ]
            ]);
            
            if ($response->failed()) {
                Log::error('Hugging Face API Error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Failed to generate quiz questions: ' . $response->status(),
                    'data' => null
                ];
            }
            
            $result = $response->json();
            
            // Handle different response formats
            if (isset($result['error'])) {
                Log::error('Hugging Face Model Error: ' . $result['error']);
                return [
                    'success' => false,
                    'message' => 'Model error: ' . $result['error'],
                    'data' => null
                ];
            }
            
            // Extract generated text
            $generatedText = '';
            if (isset($result[0]['generated_text'])) {
                $generatedText = $result[0]['generated_text'];
            } elseif (is_string($result)) {
                $generatedText = $result;
            } else {
                Log::error('Unexpected API response format', ['response' => $result]);
                // Generate fallback questions
                return [
                    'success' => true,
                    'message' => 'Generated fallback questions',
                    'data' => $this->generateFallbackQuestions($studentProfile)
                ];
            }
            
            // Parse the generated questions
            $questions = $this->parseGeneratedQuestions($generatedText);
            
            return [
                'success' => true,
                'message' => 'Quiz questions generated successfully',
                'data' => $questions
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Quiz Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error generating quiz questions: ' . $e->getMessage(),
                'data' => null
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
        $profileData = [
            'major_subject' => $studentProfile->major_subject,
            'current_position' => $studentProfile->current_position,
            'specialization_field' => $studentProfile->specialization_field,
            'preferred_technologies' => $studentProfile->preferred_technologies,
            'current_skill_level' => $studentProfile->current_skill_level,
            'main_goal' => $studentProfile->main_goal,
            'time_per_week' => $studentProfile->time_per_week,
        ];
        
        $prompt = "Generate 5 quiz questions with multiple choice options (A, B, C, D) and correct answers based on this student profile:\n\n";
        
        foreach ($profileData as $key => $value) {
            if (!empty($value)) {
                $prompt .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }
        
        $prompt .= "\nReturn ONLY a JSON array with 5 questions in this exact format:\n";
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
        
        // Try to extract JSON array from the generated text
        $jsonStart = strpos($generatedText, '[');
        $jsonEnd = strrpos($generatedText, ']');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($generatedText, $jsonStart, $jsonEnd - $jsonStart + 1);
            
            $parsedData = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsedData) && count($parsedData) > 0) {
                // Ensure we have exactly 5 questions
                while (count($parsedData) < 5) {
                    $parsedData[] = [
                        'question' => 'Sample question ' . (count($parsedData) + 1),
                        'options' => ['A) Option 1', 'B) Option 2', 'C) Option 3', 'D) Option 4'],
                        'correct_answer' => 'A) Option 1'
                    ];
                }
                
                return [
                    'questions' => array_slice($parsedData, 0, 5)
                ];
            }
        }
        
        // If JSON parsing fails, create a simple format
        return $this->createSimpleQuestionsFormat($generatedText);
    }
    
    /**
     * Create a simple questions format if JSON parsing fails
     *
     * @param string $generatedText
     * @return array
     */
    private function createSimpleQuestionsFormat($generatedText)
    {
        // Create fallback questions based on student profile
        return [
            'questions' => [
                [
                    'question' => 'What is your major subject?',
                    'options' => ['A) Computer Science', 'B) Mathematics', 'C) Physics', 'D) Biology'],
                    'correct_answer' => 'A) Computer Science'
                ],
                [
                    'question' => 'What is your specialization field?',
                    'options' => ['A) Data Science', 'B) Web Development', 'C) Mobile Apps', 'D) AI/ML'],
                    'correct_answer' => 'B) Web Development'
                ],
                [
                    'question' => 'What is your current skill level?',
                    'options' => ['A) Beginner', 'B) Intermediate', 'C) Advanced', 'D) Expert'],
                    'correct_answer' => 'B) Intermediate'
                ],
                [
                    'question' => 'What is your main goal?',
                    'options' => ['A) Get a job', 'B) Learn new skills', 'C) Get a degree', 'D) Start a business'],
                    'correct_answer' => 'A) Get a job'
                ],
                [
                    'question' => 'How much time per week can you dedicate?',
                    'options' => ['A) 5 hours', 'B) 10 hours', 'C) 15 hours', 'D) 20+ hours'],
                    'correct_answer' => 'D) 20+ hours'
                ]
            ]
        ];
    }
    
    /**
     * Generate fallback questions based on student profile
     *
     * @param StudentProfile $studentProfile
     * @return array
     */
    private function generateFallbackQuestions(StudentProfile $studentProfile)
    {
        $questions = [];
        
        // Question 1: Based on major subject
        $questions[] = [
            'question' => 'What is the importance of ' . ($studentProfile->major_subject ?? 'your field of study') . ' in today\'s world?',
            'options' => [
                'A) It has no practical application',
                'B) It is only useful for academic purposes',
                'C) It plays a crucial role in technological advancement',
                'D) It is becoming obsolete'
            ],
            'correct_answer' => 'C) It plays a crucial role in technological advancement'
        ];
        
        // Question 2: Based on specialization
        $questions[] = [
            'question' => 'In ' . ($studentProfile->specialization_field ?? 'your specialization') . ', which skill is most important?',
            'options' => [
                'A) Theoretical knowledge',
                'B) Practical implementation',
                'C) Communication skills',
                'D) All of the above'
            ],
            'correct_answer' => 'D) All of the above'
        ];
        
        // Question 3: Based on technologies
        $techs = explode(',', $studentProfile->preferred_technologies ?? 'general technologies');
        $primaryTech = trim($techs[0]);
        $questions[] = [
            'question' => 'What is the primary purpose of ' . $primaryTech . '?',
            'options' => [
                'A) To complicate development process',
                'B) To solve specific technical problems',
                'C) To increase code complexity',
                'D) To replace all other technologies'
            ],
            'correct_answer' => 'B) To solve specific technical problems'
        ];
        
        // Question 4: Based on skill level
        $questions[] = [
            'question' => 'As a ' . ($studentProfile->current_skill_level ?? 'learner') . ', what should be your primary focus?',
            'options' => [
                'A) Learning advanced concepts without basics',
                'B) Building a strong foundation and practicing',
                'C) Only watching tutorials',
                'D) Copying others\' code without understanding'
            ],
            'correct_answer' => 'B) Building a strong foundation and practicing'
        ];
        
        // Question 5: Based on goals
        $questions[] = [
            'question' => 'To achieve your goal of ' . ($studentProfile->main_goal ?? 'career advancement') . ', what is the most important step?',
            'options' => [
                'A) Networking with professionals',
                'B) Continuous learning and skill development',
                'C) Just completing assignments',
                'D) Avoiding challenges'
            ],
            'correct_answer' => 'B) Continuous learning and skill development'
        ];
        
        return [
            'questions' => $questions
        ];
    }
    
    /**
     * Save generated quiz to database
     *
     * @param string $studentId
     * @param array $questionsData
     * @return StudentQuiz
     */
    public function saveQuiz($studentId, array $questionsData)
    {
        return StudentQuiz::create([
            'student_id' => $studentId,
            'questions' => json_encode($questionsData['questions']),
            'answers' => json_encode([]), // Empty initially, to be filled by student
            'score' => null // To be calculated later
        ]);
    }
}