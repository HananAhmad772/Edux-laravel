<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestHFAPICommand extends Command
{
    protected $signature = 'test:hf-api';
    protected $description = 'Test Hugging Face API connectivity';

    public function handle()
    {
        $this->info('Testing Hugging Face API connectivity...');
        
        $apiKey = env('HUGGINGFACE_API_KEY');
        $apiUrl = env('HUGGINGFACE_URL');
        
        $this->info('API Key: ' . (strlen($apiKey) > 10 ? substr($apiKey, 0, 5) . '...' . substr($apiKey, -5) : $apiKey));
        $this->info('API URL: ' . $apiUrl);
        
        if (empty($apiKey) || empty($apiUrl)) {
            $this->error('Missing API key or URL');
            return 1;
        }
        
        // Test a simple request
        $this->info('Sending test request...');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($apiUrl, [
            'model' => 'meta-llama/Llama-3.1-8B-Instruct',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Say hello'
                ]
            ]
        ]);
        
        $this->info('HTTP Status Code: ' . $response->status());
        
        if ($response->failed()) {
            $this->error('Request failed');
            $this->error('Response body: ' . $response->body());
        } else {
            $result = $response->json();
            $this->info('Response: ' . json_encode($result, JSON_PRETTY_PRINT));
            
            if (isset($result['error'])) {
                $this->error('API Error: ' . $result['error']);
            } elseif (isset($result['choices'][0]['message']['content'])) {
                $this->info('✅ Success!');
                $this->info('Generated content: ' . $result['choices'][0]['message']['content']);
            }
        }
        
        return 0;
    }
}