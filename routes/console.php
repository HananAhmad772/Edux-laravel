<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\TestRoadmapGeneration;
use App\Console\Commands\TestAIQuizCommand;
use App\Console\Commands\DebugEnvCommand;
use App\Console\Commands\DebugEnvFileCommand;
use App\Console\Commands\TestHFAPICommand;
use App\Console\Commands\TestHFAPIQuizCommand;
use App\Console\Commands\TestAIQuizServiceCommand;
use App\Console\Commands\CheckRoadmapSchemaCommand;
use App\Console\Commands\TestAIChatbotCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register the roadmap test command
Artisan::command('test:roadmap-generation', function () {
    $this->call(TestRoadmapGeneration::class);
})->purpose('Test the AI roadmap generation functionality');

// Register the AI quiz test command
Artisan::command('test:ai-quiz', function () {
    $this->call(TestAIQuizCommand::class);
})->purpose('Test the AI quiz generation functionality');

// Register the environment debug command
Artisan::command('debug:env', function () {
    $this->call(DebugEnvCommand::class);
})->purpose('Debug environment variables');

// Register the environment file debug command
Artisan::command('debug:env-file', function () {
    $this->call(DebugEnvFileCommand::class);
})->purpose('Debug .env file contents');

// Register the Hugging Face API test command
Artisan::command('test:hf-api', function () {
    $this->call(TestHFAPICommand::class);
})->purpose('Test Hugging Face API connectivity');

// Register the Hugging Face API quiz test command
Artisan::command('test:hf-api-quiz', function () {
    $this->call(TestHFAPIQuizCommand::class);
})->purpose('Test Hugging Face API with quiz generation prompt');

// Register the AI Quiz Service test command
Artisan::command('test:ai-quiz-service', function () {
    $this->call(TestAIQuizServiceCommand::class);
})->purpose('Test AI Quiz Service directly');

// Register the roadmap schema check command
Artisan::command('check:roadmap-schema', function () {
    $this->call(CheckRoadmapSchemaCommand::class);
})->purpose('Check the student_roadmaps table schema');

// Register the AI chatbot test command
Artisan::command('test:ai-chatbot', function () {
    $this->call(TestAIChatbotCommand::class);
})->purpose('Test the AI chatbot functionality');