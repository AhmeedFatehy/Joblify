<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends BaseApiController
{
    use ApiResponse;

    public function redirectToGoogle(Request $request){
        $role = $request->query('role', 'candidate'); 

        return Socialite::driver('google')
            ->with(['state' => 'role=' . $role]) 
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback(Request $request){
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            parse_str($request->get('state'), $state);
            $role = $state['role'] ?? 'candidate';

            $user = User::updateOrCreate([
                'email' => $googleUser->email,
            ], [
                'name' => $googleUser->name,
                'provider_id' => $googleUser->id,
                'provider_name' => 'google',
                'avatar' => $googleUser->avatar,
                'role' => $role, 
                'email_verified_at' => now(), 
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return redirect("http://localhost:8080/auth/callback?token={$token}");

        } catch (\Exception $e) {
            return $this->error('Google authentication failed', 401);
        }
    }

    public function redirectToGithub(Request $request){
        $role = $request->query('role', 'candidate');

        return Socialite::driver('github')
            ->with(['state' => 'role=' . $role])
            ->stateless()
            ->redirect();
    }

    public function handleGithubCallback(Request $request){
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();
            
            parse_str($request->get('state'), $state);
            $role = $state['role'] ?? 'candidate';

            $user = User::updateOrCreate([
                'email' => $githubUser->email,
            ], [
                'name' => $githubUser->name ?? $githubUser->nickname,
                'provider_id' => $githubUser->id,
                'provider_name' => 'github',
                'avatar' => $githubUser->avatar,
                'role' => $role,
                'email_verified_at' => now(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return redirect("http://localhost:8080/auth/callback?token={$token}");

        } catch (\Exception $e) {
            return $this->error('GitHub authentication failed', 401);
        }
    }
}
