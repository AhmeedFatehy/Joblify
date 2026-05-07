<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Admins bypass all policy checks
        Gate::before(function ($user, $ability) {
            return $user->role->value === 'admin' ? true : null;
        });

        // Policies
        Gate::policy(Comment::class, CommentPolicy::class);

        // User can update their own profile only
        Gate::define('update-profile', function (User $user, User $targetUser) {
            return $user->id === $targetUser->id;
        });

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
