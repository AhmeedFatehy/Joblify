<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'linkedin_url' => $request->linkedin_url,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->created([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'User registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorized('Invalid login credentials');
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $user,
            'access_token' => $token,
        ], 'Login successful');
    }

    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->noContent('Logged out successfully');
    }
}
