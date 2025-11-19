<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugEnvFileCommand extends Command
{
    protected $signature = 'debug:env-file';
    protected $description = 'Debug .env file contents';

    public function handle()
    {
        $this->info('Debugging .env file...');
        
        $envPath = base_path('.env');
        $this->info('Looking for .env file at: ' . $envPath);
        
        if (file_exists($envPath)) {
            $this->info('✅ .env file exists');
            
            $envContents = file_get_contents($envPath);
            $lines = explode("\n", $envContents);
            
            foreach ($lines as $line) {
                if (strpos($line, 'HUGGINGFACE_API_KEY=') === 0) {
                    $this->info('Found HUGGINGFACE_API_KEY line');
                    $key = substr($line, 19); // Remove 'HUGGINGFACE_API_KEY='
                    $this->info('HUGGINGFACE_API_KEY: ' . (strlen($key) > 10 ? substr($key, 0, 5) . '...' . substr($key, -5) : $key));
                }
                
                if (strpos($line, 'HUGGINGFACE_URL=') === 0) {
                    $this->info('Found HUGGINGFACE_URL line');
                    $url = substr($line, 16); // Remove 'HUGGINGFACE_URL='
                    $this->info('HUGGINGFACE_URL: ' . $url);
                }
            }
        } else {
            $this->error('❌ .env file does not exist');
        }
        
        return 0;
    }
}