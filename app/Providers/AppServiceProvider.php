<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AIQuizService;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}