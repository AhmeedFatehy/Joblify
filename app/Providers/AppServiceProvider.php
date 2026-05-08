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
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

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
        VerifyEmail::createUrlUsing(function ($notifiable) {
        $frontendUrl = 'http://localhost:5173/verify-email'; 

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return $frontendUrl . '?queryURL=' . urlencode($verifyUrl);
    });

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
