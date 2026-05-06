<?php

namespace App\Providers;

use App\Models\User;
use App\Services\GeminiEvaluationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeminiEvaluationService::class);
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
