<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdminCommentController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminJobController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;

use App\Http\Controllers\Api\VerifyEmailController;

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\EmployerAnalyticsController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SkillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{job}', [JobController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/suggest', [SkillController::class, 'suggest']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed']) 
    ->name('verification.verify');

// Protected
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend']);

    // candidate
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::middleware('verified')->group(function () {
        Route::post('/profile', [ProfileController::class, 'update']);
        Route::get('/profile/resume', [ProfileController::class, 'downloadResume']);
    });

    //  Jobs
    Route::post('/jobs', [JobController::class, 'store']);
    Route::patch('/jobs/{job}', [JobController::class, 'update']);
    Route::delete('/jobs/{job}', [JobController::class, 'destroy']);
    Route::get('employer/jobs', [JobController::class, 'employerJobs']);

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

    // Company
    Route::middleware('role:employer','verified')->group(function () {
        Route::get('/company', [CompanyController::class, 'show']);
        Route::post('/company', [CompanyController::class, 'store']);
    });

    //  Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/activity', [AdminDashboardController::class, 'activity']);
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        // Job Moderation
        Route::get('/jobs', [AdminJobController::class, 'index']);
        Route::patch('/jobs/{job}/approve', [AdminJobController::class, 'approve']);
        Route::patch('/jobs/{job}/reject', [AdminJobController::class, 'reject']);
        // Bulk moderation (optional)
        Route::post('/jobs/bulk-approve', [AdminJobController::class, 'bulkApprove']);
        Route::post('/jobs/bulk-reject', [AdminJobController::class, 'bulkReject']);

        // Comment Moderation
        Route::get('/comments', [AdminCommentController::class, 'index']);
        Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);

         // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Skills
        Route::post('/skills', [SkillController::class, 'store']);
        Route::patch('/skills/{skill}', [SkillController::class, 'update']);
        Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);

        // Users management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
        Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate']);
    });

});
