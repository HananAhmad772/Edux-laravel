<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestHFAPIQuizCommand extends Command
{
    protected $signature = 'test:hf-api-quiz';
    protected $description = 'Test Hugging Face API with quiz generation prompt';

    public function handle()
    {
        $this->info('Testing Hugging Face API with quiz generation prompt...');
        
        $apiKey = env('HUGGINGFACE_API_KEY');
        $apiUrl = env('HUGGINGFACE_URL');
        
        $this->info('API Key: ' . (strlen($apiKey) > 10 ? substr($apiKey, 0, 5) . '...' . substr($apiKey, -5) : $apiKey));
        $this->info('API URL: ' . $apiUrl);
        
        // Use the same prompt as in AIQuizService
        $prompt = "You are an intelligent AI quiz generator that creates short, adaptive quizzes to test a user's understanding in their chosen technical field.

Below is the user's profile data:

Main Area of Interest: Web Development
Current Situation: Looking for a job
Specialization: Backend Development
Preferred Technologies: Node.js
Current Skill Level: Beginner
Main Goal: Get a job
Time per Week: 5-10 hours

🎯 Your task:
Based on this profile, generate **5 quick quiz questions** that:
1. Are related to the user's specialization and preferred technologies.
2. Include both **theoretical** and **conceptual/problem-based** questions.
3. Match the user's **skill level** (Beginner, Intermediate, or Advanced).
4. Help evaluate the user's current position and understanding in this field.
5. Include questions that can help identify knowledge gaps for creating a personalized learning roadmap.
6. Use clear, concise phrasing suitable for an online quiz.

🧠 Example:
If the user is an intermediate web developer using JavaScript and React, include:
- a question about JavaScript closures
- one about React component lifecycle
- one about state management
- one about REST API integration
- one conceptual question that checks problem-solving approach.

Now, generate the quiz questions accordingly.

Return ONLY a JSON array with 5 questions in this exact format:
[
  {
    \"question\": \"Question text here?\",
    \"options\": [\"A) Option 1\", \"B) Option 2\", \"C) Option 3\", \"D) Option 4\"],
    \"correct_answer\": \"A) Option 1\"
  }
]";

        $this->info('Sending quiz generation request...');
        $this->info('Prompt length: ' . strlen($prompt));
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($apiUrl, [
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
        
        $this->info('HTTP Status Code: ' . $response->status());
        
        if ($response->failed()) {
            $this->error('Request failed');
            $this->error('Response body: ' . $response->body());
        } else {
            $result = $response->json();
            $this->info('Response received');
            
            if (isset($result['error'])) {
                $this->error('API Error: ' . $result['error']);
            } elseif (isset($result['choices'][0]['message']['content'])) {
                $this->info('✅ Success!');
                $generatedContent = $result['choices'][0]['message']['content'];
                $this->info('Generated content length: ' . strlen($generatedContent));
                $this->info('Generated content: ' . $generatedContent);
                
                // Try to parse as JSON
                $jsonStart = strpos($generatedContent, '[');
                $jsonEnd = strrpos($generatedContent, ']');
                
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonString = substr($generatedContent, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $parsedData = json_decode($jsonString, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->info('✅ Successfully parsed JSON with ' . count($parsedData) . ' questions');
                        $this->info('First question: ' . ($parsedData[0]['question'] ?? 'N/A'));
                    } else {
                        $this->error('Failed to parse JSON: ' . json_last_error_msg());
                    }
                } else {
                    $this->error('Could not find JSON array in response');
                }
            } else {
                $this->error('Unexpected response format');
                $this->info('Full response: ' . json_encode($result, JSON_PRETTY_PRINT));
            }
        }
        
        return 0;
    }
}