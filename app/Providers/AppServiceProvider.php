<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AIQuizService;
use App\Services\AIRoadmapService;
use App\Services\AIChatbotService;
use App\Services\AIChatbotMediatorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AIQuizService::class, function ($app) {
            return new AIQuizService();
        });
        
        $this->app->singleton(AIRoadmapService::class, function ($app) {
            return new AIRoadmapService();
        });
        
        $this->app->singleton(AIChatbotService::class, function ($app) {
            return new AIChatbotService();
        });
        
        $this->app->singleton(AIChatbotMediatorService::class, function ($app) {
            return new AIChatbotMediatorService($app->make(AIChatbotService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}