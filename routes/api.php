<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::ApiResource('jobs', \App\Http\Controllers\JobController::class);
Route::ApiResource('companies', \App\Http\Controllers\CompanyController::class);
Route::ApiResource('categories', \App\Http\Controllers\CategoryController::class);
Route::ApiResource('skills', \App\Http\Controllers\SkillController::class);
Route::ApiResource('notifications', \App\Http\Controllers\NotificationController::class);
