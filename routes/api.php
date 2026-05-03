<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
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
// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store']);


    //Role-Based Routes

    //Note to Team: Use 'role:admin', 'role:employer', or 'role:candidate' to protect your specific routes.
    
    // Example for Admin Routes:
    // Route::middleware('role:admin')->group(function () {
    //     Route::get('/admin/dashboard', [AdminController::class, 'index']);
    // });

});



// TODO: Implement these controllers in their respective Epic tasks
// Route::apiResource('jobs', App\Http\Controllers\Api\JobController::class);
// Route::apiResource('companies', App\Http\Controllers\Api\CompanyController::class);
// Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);
// Route::apiResource('skills', App\Http\Controllers\Api\SkillController::class);
// Route::apiResource('notifications', App\Http\Controllers\Api\NotificationController::class);
// Route::apiResource('applications', App\Http\Controllers\Api\ApplicationController::class);
// Route::apiResource('comments', App\Http\Controllers\Api\CommentController::class);


//when create admin routes use role:admin middleware
// Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
//     Route::get('/admin/stats', [AdminController::class, 'index']);
// });