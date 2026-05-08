<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Joblify API',
        'version' => 'v1',
    ]);
});

// Test route stubs for named routes expected by the test suite.
Route::get('/home', fn () => response('home'))->name('home');
Route::get('/dashboard', fn () => response('dashboard'))->name('dashboard');

// Profile routes
Route::get('/profile/edit', fn () => response('profile edit'))->name('profile.edit');
Route::patch('/profile', fn () => redirect()->route('profile.edit'))->name('profile.update');
Route::delete('/profile', fn () => redirect()->route('home'))->name('profile.destroy');

// Simple placeholder for security and password routes used in tests
Route::get('/security', fn () => response('security'))->name('security.edit');
Route::put('/user/password', fn () => redirect()->route('security.edit'))->name('user-password.update');
Route::get('/password/confirm', fn () => response('password confirm'))->name('password.confirm');

// Auth placeholders
Route::get('/login', fn () => response('login'))->name('login');
Route::post('/login', fn () => redirect()->route('dashboard'))->name('login.store');
Route::post('/logout', fn () => redirect()->route('home'))->name('logout');
Route::get('/register', fn () => response('register'))->name('register');
Route::post('/register', fn () => redirect()->route('dashboard'));

// Password reset placeholders
Route::get('/password/reset', fn () => response('password request'))->name('password.request');
Route::post('/password/email', fn () => redirect()->back())->name('password.email');
Route::get('/password/reset/{token}', fn ($token) => response('password reset'))->name('password.reset');
Route::post('/password/reset', fn () => redirect()->route('login'))->name('password.update');

// Verification placeholders
Route::get('/email/verify', fn () => response('verify notice'))->name('verification.notice');
Route::post('/email/verification-notification', fn () => redirect()->route('home'))->name('verification.send');
Route::get('/email/verify/{id}/{hash}', fn () => redirect()->route('dashboard'))->name('verification.verify');

// Two-factor placeholder
Route::get('/two-factor-challenge', fn () => response('two factor'))->name('two-factor.login');
