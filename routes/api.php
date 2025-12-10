<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ActivityController;

use App\Http\Controllers\Api\BuildingController;
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
Route::get('/real-estate/search', [PropertyController::class, 'search']);
Route::get('/real-estate/{property}', [PropertyController::class, 'show']);
Route::get('/real-estate/categories', [CategoryController::class, 'index']);
Route::get('/real-estate/plans', [PlanController::class, 'index']);
Route::post('/real-estate/{property}/contact', [PropertyController::class, 'sendContact']);

// Buildings - Public routes
Route::get('/real-estate/buildings', [BuildingController::class, 'index']);
Route::get('/real-estate/buildings/{building}', [BuildingController::class, 'show']);

// Real Estate API - Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/real-estate', [PropertyController::class, 'store']);
    Route::put('/real-estate/{property}', [PropertyController::class, 'update']);
    Route::delete('/real-estate/{property}', [PropertyController::class, 'destroy']);

    Route::post('/real-estate/{property}/favorite', [PropertyController::class, 'toggleFavorite']);
    Route::get('/user/favorites', [FavoriteController::class, 'list']);
    
    // User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update']); // Ensure update method exists

    // Agent Profile
        Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Crm\TicketController::class, 'show']);

        // Associations
        Route::post('/associations', [\App\Http\Controllers\Api\Crm\AssociationController::class, 'store']);
    Route::put('/agent/profile', [AgentController::class, 'update']);
    Route::get('/agent/stats', [AgentController::class, 'stats']);

    // ...

    // Buildings - Auth routes
    Route::post('/real-estate/buildings', [BuildingController::class, 'store']);
    Route::put('/real-estate/buildings/{building}', [BuildingController::class, 'update']);
    Route::delete('/real-estate/buildings/{building}', [BuildingController::class, 'destroy']);
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
