<?php

use App\Http\Controllers\Api\Admin\AdminCommentController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminJobController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EmployerAnalyticsController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{job}', [JobController::class, 'show']);

// Protected
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    //  Jobs
    Route::middleware('role:employer')->group(function () {
        Route::post('/jobs', [JobController::class, 'store']);
        Route::patch('/jobs/{job}', [JobController::class, 'update']);
        Route::delete('/jobs/{job}', [JobController::class, 'destroy']);
        Route::get('employer/jobs', [JobController::class, 'employerJobs']);
    });

    //  Applications
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store']);
    Route::get('/jobs/{job}/applications', [ApplicationController::class, 'jobApplications']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);
    Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);

    //  Comments
    Route::get('/jobs/{job}/comments', [CommentController::class, 'index']);
    Route::post('/jobs/{job}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    //  Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    //  Employer Analytics
    Route::middleware('role:employer')->group(function () {
        Route::get('/employer/analytics', [EmployerAnalyticsController::class, 'index']);
        Route::get('/employer/jobs/{job}/applications', [EmployerAnalyticsController::class, 'jobApplications']);
    });

    //  Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/activity', [AdminDashboardController::class, 'activity']);

        // Job Moderation
        Route::get('/jobs', [AdminJobController::class, 'index']);
        Route::post('/jobs/{job}/approve', [AdminJobController::class, 'approve']);
        Route::post('/jobs/{job}/reject', [AdminJobController::class, 'reject']);

        // Comment Moderation
        Route::get('/comments', [AdminCommentController::class, 'index']);
        Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);
    });

});
