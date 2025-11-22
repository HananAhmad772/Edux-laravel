<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatbotService
{
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct()
    {
        $this->apiKey = env('HUGGINGFACE_API_KEY');
        $this->apiUrl = env('HUGGINGFACE_URL', 'https://router.huggingface.co/v1/chat/completions');
        // Using a known working model
        $this->model = 'Qwen/Qwen3-Coder-30B-A3B-Instruct:nebius';
    }
    // model="Qwen/Qwen3-Coder-30B-A3B-Instruct:nebius",
    /**
     * Generate a response from the AI chatbot
     *
     * @param array $messages
     * @return array
     */
    public function generateResponse(array $messages)
    {
        try {
            Log::info('AI Chatbot: Generating response', [
                'model' => $this->model,
                'messages_count' => count($messages)
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7
            ]);
            
            Log::info('AI Chatbot: API response received', [
                'status_code' => $response->status(),
                'response_length' => strlen($response->body())
            ]);
            
            if ($response->failed()) {
                Log::error('AI Chatbot: API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to generate response from AI chatbot',
                    'error' => $response->body()
                ];
            }
            
            $result = $response->json();
            
            if (isset($result['error'])) {
                Log::error('AI Chatbot: Model Error', [
                    'error' => $result['error']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'AI model returned an error',
                    'error' => $result['error']
                ];
            }
            
            $generatedText = '';
            if (isset($result['choices'][0]['message']['content'])) {
                $generatedText = $result['choices'][0]['message']['content'];
            } elseif (isset($result[0]['generated_text'])) {
                $generatedText = $result[0]['generated_text'];
            } else {
                Log::error('AI Chatbot: Unexpected API response format', [
                    'response' => $result
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from AI model',
                    'error' => 'Unknown response format'
                ];
            }
            
            Log::info('AI Chatbot: Response generated successfully', [
                'generated_text_length' => strlen($generatedText)
            ]);
            
            return [
                'success' => true,
                'message' => 'Response generated successfully',
                'data' => $generatedText
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Chatbot: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating response',
                'error' => $e->getMessage()
            ];
        }
    }
}