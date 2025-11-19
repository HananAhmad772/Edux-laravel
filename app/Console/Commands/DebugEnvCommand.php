<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugEnvCommand extends Command
{
    protected $signature = 'debug:env';
    protected $description = 'Debug environment variables';

    public function handle()
    {
        $this->info('Debugging environment variables...');
        
        $apiKey = env('HUGGINGFACE_API_KEY');
        $apiUrl = env('HUGGINGFACE_URL');
        
        $this->info('HUGGINGFACE_API_KEY: ' . ($apiKey ? substr($apiKey, 0, 5) . '...' . substr($apiKey, -5) : 'NOT SET'));
        $this->info('HUGGINGFACE_URL: ' . ($apiUrl ?: 'NOT SET'));
        
        // Test if the values are accessible
        if ($apiKey && $apiUrl) {
            $this->info('✅ Environment variables are set');
        } else {
            $this->error('❌ Environment variables are missing');
        }
        
        return 0;
    }
}