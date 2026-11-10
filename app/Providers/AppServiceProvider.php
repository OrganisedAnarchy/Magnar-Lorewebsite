<?php

namespace App\Providers;

use App\Support\DailyTasks;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(DailyTasks $dailyTasks): void
    {
        View::composer('admin.daily-tasks.tray', function (ViewInstance $view) use ($dailyTasks): void {
            $view->with('tasks', $dailyTasks->recommendations());
        });
    }
}
