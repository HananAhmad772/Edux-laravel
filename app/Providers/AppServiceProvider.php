<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AIQuizService;
use App\Services\AIRoadmapService;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}