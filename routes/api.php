<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\FavoriteController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});




Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'profile']);
});

// Real Estate API - Public routes
Route::get('/real-estate', [PropertyController::class, 'index']);
Route::get('/real-estate/{property}', [PropertyController::class, 'show']);
Route::get('/real-estate/categories', [CategoryController::class, 'index']);
Route::get('/real-estate/plans', [PlanController::class, 'index']);
Route::post('/real-estate/{property}/contact', [PropertyController::class, 'sendContact']);

// Real Estate API - Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/real-estate', [PropertyController::class, 'store']);
    Route::put('/real-estate/{property}', [PropertyController::class, 'update']);
    Route::delete('/real-estate/{property}', [PropertyController::class, 'destroy']);

    Route::post('/real-estate/{property}/favorite', [PropertyController::class, 'toggleFavorite']);
    Route::get('/user/favorites', [FavoriteController::class, 'list']);
});

// Admin only routes (add middleware if you have one for admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/real-estate/categories', [CategoryController::class, 'store']);
    Route::put('/real-estate/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/real-estate/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/real-estate/plans', [PlanController::class, 'store']);
    Route::put('/real-estate/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('/real-estate/plans/{plan}', [PlanController::class, 'destroy']);
});
