<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Nursery administration: child record management, classrooms,
        // billing, pickup list administration. Owner and manager only.
        Gate::define('manage-nursery', fn (User $user) => $user->manages());

        // General health notes are restricted; allergies stay visible to all
        // staff because they are safety-critical at meal and activity time.
        Gate::define('view-health-notes', fn (User $user) => $user->manages());

        // Staff accounts are managed by the owner only.
        Gate::define('manage-users', fn (User $user) => $user->isOwner());
    }
}
