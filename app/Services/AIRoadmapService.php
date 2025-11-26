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
                'max_tokens' => 3000,
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
**Week 1–2: (Topic Heading)**
**Week 3–4: (Topic Heading)**

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
- Under each step (normal formatting, no bold), you MUST include these EXACT four headings in this EXACT order:

    Topics to study:
        * You MUST generate SPECIFIC and RELEVANT bullet points (at least 7, preferably 8-10).
        * Each topic should be a concrete learning subject or concept.
        * Examples: 'Introduction to REST APIs', 'Database normalization', 'React component lifecycle'
        * Do NOT use generic placeholders like 'Topic 1', 'Topic 2', etc.
        * Format each topic as: * [topic name]

    Tools to use:
        * Always include this heading exactly as shown (case sensitive).
        * List specific tools, frameworks, or technologies.
        * Format each tool as: * [tool name]
        * If no tools, list: * None specified

    Skills learned:
        * Always include this heading exactly as shown (case sensitive).
        * List specific skills that will be acquired.
        * Format each skill as: * [skill name]
        * If no skills, list: * None specified

    Mini practice tasks or micro-projects:
        * Always include this heading exactly as shown (case sensitive).
        * List hands-on practical tasks or small projects.
        * Format each task as: * [task name]
        * If no tasks, list: * None specified

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

STRICT FORMAT REQUIREMENTS:
- Every step MUST include all four headings in this EXACT order: \"Topics to study:\", \"Tools to use:\", \"Skills learned:\", \"Mini practice tasks or micro-projects:\"
- The \"Topics to study:\" section MUST contain at least 1 SPECIFIC bullet point.
- Do NOT change the ordering or spelling of headings. Use exactly these headings spelled as shown (case sensitive).
- Do NOT use generic placeholders like 'Topic 1', 'Topic 2', etc.
- Make all content RELEVANT to the student's profile and goal.
- Use bullet points with asterisk (*) for all list items.


Ensure the output is clean, structured, and easy for frontend integration.
";
// - Never use '+', '-', '•', or any other symbols for bullets.
// - Maintain the exact indentation and spacing as shown.
// - Do not invent new list formatting.

        return $prompt;
    }
    
    /**
     * Validate parsed roadmap structure
     *
     * @param array $parsedStructure
     * @return array
     */
    public function validateParsedStructure(array $parsedStructure)
    {
        $errors = [];
        
        if (!isset($parsedStructure['steps']) || !is_array($parsedStructure['steps'])) {
            $errors[] = 'Missing or invalid steps array';
            return ['valid' => false, 'errors' => $errors];
        }
        
        $steps = $parsedStructure['steps'];
        
        if (empty($steps)) {
            $errors[] = 'No steps found in roadmap';
            return ['valid' => false, 'errors' => $errors];
        }
        
        foreach ($steps as $index => $step) {
            $stepNum = $index + 1;
            
            // Check required headings
            $requiredHeadings = ['Topics to study', 'Tools to use', 'Skills learned', 'Mini practice tasks or micro-projects'];
            $hasAllHeadings = true;
            
            // Check topics count (now between 4-8 instead of exactly 6)
            if (!isset($step['topics']) || !is_array($step['topics'])) {
                $errors[] = "Step {$stepNum}: Missing topics array";
                $hasAllHeadings = false;
            } else {
                $topicsCount = count($step['topics']);
                if ($topicsCount < 1) {
                    $errors[] = "Step {$stepNum}: Expected at least 1 topic, found {$topicsCount}";
                }
            }
            
            // Check other sections exist (can be empty)
            if (!isset($step['tools']) || !is_array($step['tools'])) {
                $errors[] = "Step {$stepNum}: Missing tools array";
                $hasAllHeadings = false;
            }
            
            if (!isset($step['skills']) || !is_array($step['skills'])) {
                $errors[] = "Step {$stepNum}: Missing skills array";
                $hasAllHeadings = false;
            }
            
            if (!isset($step['tasks']) || !is_array($step['tasks'])) {
                $errors[] = "Step {$stepNum}: Missing tasks array";
                $hasAllHeadings = false;
            }
            
            // Check heading exists
            if (!isset($step['heading']) || empty($step['heading'])) {
                $errors[] = "Step {$stepNum}: Missing step heading";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Fix the structure of a single step to ensure it has a reasonable number of topics
     *
     * @param array $stepLines
     * @return array
     */
    private function fixStepStructure($stepLines)
    {
        $fixedLines = [];
        $topicsSection = false;
        $topicsLines = [];
        $otherSections = [];
        $currentSection = '';
        
        // Find the step heading (first line)
        $fixedLines[] = $stepLines[0];
        
        // Process the rest of the lines
        for ($i = 1; $i < count($stepLines); $i++) {
            $line = $stepLines[$i];
            $trimmedLine = trim($line);
            
            // Check for section headings
            if (strpos($trimmedLine, 'Topics to study') !== false) {
                $topicsSection = true;
                $currentSection = 'topics';
                $otherSections[] = $line;
            } else if (strpos($trimmedLine, 'Tools to use') !== false) {
                $topicsSection = false;
                $currentSection = 'tools';
                $otherSections[] = $line;
            } else if (strpos($trimmedLine, 'Skills learned') !== false) {
                $topicsSection = false;
                $currentSection = 'skills';
                $otherSections[] = $line;
            } else if (strpos($trimmedLine, 'Mini practice tasks or micro-projects') !== false) {
                $topicsSection = false;
                $currentSection = 'tasks';
                $otherSections[] = $line;
            } else if ($topicsSection && preg_match('/^\s*\*\s*(.+)$/', $trimmedLine, $matches)) {
                // This is a topic line
                $topicsLines[] = $line;
                // Don't add to otherSections yet, we'll add them all together later
            } else if (!empty($trimmedLine)) {
                // Other content (non-empty lines)
                $otherSections[] = $line;
            } else if (empty($trimmedLine)) {
                // Empty lines
                $otherSections[] = $line;
            }
        }
        
        // Ensure we have at least 1 topic (add a placeholder if none exist)
        if (count($topicsLines) < 1) {
            $topicsLines[] = "            * Introduction to the topics for this week";
        }
        
        // Combine all lines in the correct order
        $fixedLines = array_merge($fixedLines, $otherSections);
        
        // Insert the topics at the correct position (right after "Topics to study:")
        $insertPosition = 1; // Default to after the step heading
        for ($i = 1; $i < count($fixedLines); $i++) {
            if (strpos($fixedLines[$i], 'Topics to study') !== false) {
                $insertPosition = $i + 1;
                break;
            }
        }
        
        // Insert the topics at the correct position
        if (!empty($topicsLines)) {
            array_splice($fixedLines, $insertPosition, 0, $topicsLines);
        }
        
        return $fixedLines;
    }
    
    /**
     * Parse roadmap content into structured JSON format
     *
     * @param string $roadmapContent
     * @return array
     */

        public function parseRoadmapToStructuredJson($roadmapContent)
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

                    // When a new step begins, push the previous one
                    if ($currentStep !== null) {
                        $steps[] = $currentStep;
                    }

                    $stepName = "Week {$matches[1]}–{$matches[2]}";
                    $stepTitle = $matches[3];

                    $currentStep = [
                        'heading' => $trimmedLine,
                        'title' => $stepTitle,
                        'duration' => $stepName,
                        'topics' => [],
                        'tools' => [],
                        'skills' => [],
                        'tasks' => []
                    ];

                    $currentSection = null;
                    continue;
                }

                if ($currentStep) {

                    // Section headings
                    if ($trimmedLine === 'Topics to study:') {
                        $currentSection = 'topics';
                        continue;
                    } elseif ($trimmedLine === 'Tools to use:') {
                        $currentSection = 'tools';
                        continue;
                    } elseif ($trimmedLine === 'Skills learned:') {
                        $currentSection = 'skills';
                        continue;
                    } elseif ($trimmedLine === 'Mini practice tasks or micro-projects:') {
                        $currentSection = 'tasks';
                        continue;
                    }

                    // List item with *
                    if ($currentSection && preg_match('/^\s*\*\s+(.+)$/', $trimmedLine, $matches)) {
                        $item = trim($matches[1]);

                        if (!empty($item)) {
                            $currentStep[$currentSection][] = $item;
                        }

                        continue;
                    }

                    // List item with +
                    if ($currentSection && preg_match('/^\s*\+\s+(.+)$/', $trimmedLine, $matches)) {
                        $item = trim($matches[1]);

                        if (!empty($item)) {
                            $currentStep[$currentSection][] = $item;
                        }

                        continue;
                    }
                }
            }

            // Push final step
            if ($currentStep !== null) {
                $steps[] = $currentStep;
            }

            return [
                'steps' => $steps,
                'meta' => [
                    'total_steps' => count($steps),
                    'parsed_at' => date('c')
                ]
            ];
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

        // Parse roadmap to structured JSON
        $parsedJson = $this->parseRoadmapToStructuredJson($roadmapContent);

        Log::info('Parsed JSON:', [
            'parsed' => $parsedJson
        ]);

        // Validate structure before saving
        $validation = $this->validateParsedStructure($parsedJson);

        if (!$validation['valid']) {
            Log::warning('Saving roadmap with validation errors', [
                'student_id' => $studentId,
                'errors' => $validation['errors']
            ]);
        }

        try {
            $roadmap = StudentRoadmap::create([
                'student_id' => $studentId,
                'roadmap_content' => $roadmapContent,
                'roadmap_json' => json_encode($parsedJson)
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving roadmap:', [
                'student_id' => $studentId,
                'message' => $e->getMessage()
            ]);

            return null;
        }

        // Ensure parsed steps is an array
        $steps = (is_array($parsedJson) && isset($parsedJson['steps']))
            ? $parsedJson['steps']
            : [];

        Log::info('Roadmap saved successfully', [
            'student_id' => $studentId,
            'roadmap_id' => $roadmap->id,
            'steps_count' => count($steps)
        ]);

        return $roadmap;
    }

}