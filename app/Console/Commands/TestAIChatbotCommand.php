<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIChatbotService;

class TestAIChatbotCommand extends Command
{
    protected $signature = 'test:ai-chatbot';
    protected $description = 'Test the AI chatbot functionality';

    public function handle()
    {
        $this->info('Testing AI Chatbot...');
        
        // Initialize the AI chatbot service
        $chatbotService = new AIChatbotService();
        
        // Test messages
        $messages = [
            [
                'role' => 'user',
                'content' => 'Hello, I need help with PHP arrays. Can you explain how to use them?'
            ]
        ];
        
        $this->info('Sending message to AI chatbot...');
        $this->info('Message: ' . $messages[0]['content']);
        
        // Generate response
        $result = $chatbotService->generateResponse($messages);
        
        if ($result['success']) {
            $this->info('✅ AI chatbot response received successfully!');
            $this->info('Response:');
            $this->line($result['data']);
        } else {
            $this->error('❌ Failed to get response from AI chatbot');
            $this->error('Error: ' . $result['message']);
            if (isset($result['error'])) {
                $this->error('Details: ' . $result['error']);
            }
        }
        
        return 0;
    }
}