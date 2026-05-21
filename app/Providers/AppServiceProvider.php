<?php

namespace App\Providers;

use App\Contracts\AiEvaluationService;
use App\Models\User;
use App\Services\GeminiEvaluationService;
use App\Services\RunwareEvaluationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiEvaluationService::class, function ($app) {
            return match (config('ai.provider', 'gemini')) {
                'runware' => new RunwareEvaluationService(),
                default   => new GeminiEvaluationService(),
            };
        });

        // Backward compat: resolve old concrete class name to the interface
        $this->app->alias(AiEvaluationService::class, GeminiEvaluationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.sidebar', function ($view) {
            $pendingParentRequestsCount = 0;
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                $pendingParentRequestsCount = User::role('parent')
                    ->where('parent_status', User::PARENT_STATUS_PENDING)
                    ->count();
            }
            $view->with('pendingParentRequestsCount', $pendingParentRequestsCount);
        });
    }
}
