<?php

use App\Http\Controllers\Api\ApplicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes in this file are stateless and assigned the "api" middleware
| group by default. Responses are always JSON.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store']);
});

// TODO: Implement these controllers in their respective Epic tasks
// Route::apiResource('jobs', App\Http\Controllers\Api\JobController::class);
// Route::apiResource('companies', App\Http\Controllers\Api\CompanyController::class);
// Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);
// Route::apiResource('skills', App\Http\Controllers\Api\SkillController::class);
// Route::apiResource('notifications', App\Http\Controllers\Api\NotificationController::class);
// Route::apiResource('applications', App\Http\Controllers\Api\ApplicationController::class);
// Route::apiResource('comments', App\Http\Controllers\Api\CommentController::class);
