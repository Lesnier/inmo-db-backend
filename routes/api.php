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
use App\Http\Controllers\Api\MediaController;

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

// Specific routes MUST be before wildcard {property}
Route::get('/real-estate/categories', [CategoryController::class, 'index']);
Route::get('/real-estate/plans', [PlanController::class, 'index']);

// Buildings - Public routes
Route::get('/real-estate/buildings', [BuildingController::class, 'index']);
Route::get('/real-estate/buildings/{building}', [BuildingController::class, 'show']);

// {property} routes moved to end to avoid collision with /mine

// Real Estate API - Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/real-estate', [PropertyController::class, 'store']);
    Route::put('/real-estate/{property}', [PropertyController::class, 'update']);
    Route::delete('/real-estate/{property}', [PropertyController::class, 'destroy']);
    Route::get('/real-estate/mine', [PropertyController::class, 'myProperties']);
    
    // Property Utilities
    Route::post('/real-estate/{property}/duplicate', [PropertyController::class, 'duplicate']);
    Route::put('/real-estate/{property}/archive', [PropertyController::class, 'archive']);
    Route::get('/real-estate/{property}/analytics', [PropertyController::class, 'analytics']);

    Route::post('/real-estate/{property}/favorite', [PropertyController::class, 'toggleFavorite']);
    Route::get('/user/favorites', [FavoriteController::class, 'list']);
    
    // User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update']); 
    Route::get('/user/analytics', [UserController::class, 'analytics']);

    // Agent Profile
        Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Crm\TicketController::class, 'show']);

        // Associations
        Route::post('/associations', [\App\Http\Controllers\Api\Crm\AssociationController::class, 'store']);
    Route::put('/agent/profile', [AgentController::class, 'update']);
    Route::get('/agent/stats', [AgentController::class, 'stats']);

    // CRM Routes
    Route::prefix('crm')->group(function () {
        // Contacts
        Route::get('/contacts', [\App\Http\Controllers\Api\Crm\ContactController::class, 'index']);
        Route::post('/contacts', [\App\Http\Controllers\Api\Crm\ContactController::class, 'store']);
        Route::get('/contacts/{id}', [\App\Http\Controllers\Api\Crm\ContactController::class, 'show']);
        Route::put('/contacts/{id}', [\App\Http\Controllers\Api\Crm\ContactController::class, 'update']);
        Route::delete('/contacts/{id}', [\App\Http\Controllers\Api\Crm\ContactController::class, 'destroy']);
        // Helpers
        Route::post('/contacts/{id}/assign', [\App\Http\Controllers\Api\Crm\ContactController::class, 'assign']);
        Route::get('/contacts/{id}/analytics', [\App\Http\Controllers\Api\Crm\ContactController::class, 'analytics']);

        // Companies
        Route::get('/companies', [\App\Http\Controllers\Api\Crm\CompanyController::class, 'index']);
        Route::post('/companies', [\App\Http\Controllers\Api\Crm\CompanyController::class, 'store']);
        Route::get('/companies/{id}', [\App\Http\Controllers\Api\Crm\CompanyController::class, 'show']);
        Route::put('/companies/{id}', [\App\Http\Controllers\Api\Crm\CompanyController::class, 'update']);
        Route::delete('/companies/{id}', [\App\Http\Controllers\Api\Crm\CompanyController::class, 'destroy']);

        // Deals
        Route::get('/deals', [\App\Http\Controllers\Api\Crm\DealController::class, 'index']);
        Route::post('/deals', [\App\Http\Controllers\Api\Crm\DealController::class, 'store']);
        Route::get('/deals/{id}', [\App\Http\Controllers\Api\Crm\DealController::class, 'show']);
        Route::put('/deals/{id}', [\App\Http\Controllers\Api\Crm\DealController::class, 'update']);
        Route::delete('/deals/{id}', [\App\Http\Controllers\Api\Crm\DealController::class, 'destroy']);
        // Helpers
        Route::post('/deals/{id}/stage', [\App\Http\Controllers\Api\Crm\DealController::class, 'moveStage']);
        Route::post('/deals/{id}/win', [\App\Http\Controllers\Api\Crm\DealController::class, 'markWon']);
        Route::post('/deals/{id}/lose', [\App\Http\Controllers\Api\Crm\DealController::class, 'markLost']);
        Route::get('/deals/{id}/analytics', [\App\Http\Controllers\Api\Crm\DealController::class, 'analytics']);

        // Tickets
        Route::get('/tickets', [\App\Http\Controllers\Api\Crm\TicketController::class, 'index']);
        Route::post('/tickets', [\App\Http\Controllers\Api\Crm\TicketController::class, 'store']);
        Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Crm\TicketController::class, 'show']);
        Route::put('/tickets/{id}', [\App\Http\Controllers\Api\Crm\TicketController::class, 'update']);
        Route::delete('/tickets/{id}', [\App\Http\Controllers\Api\Crm\TicketController::class, 'destroy']);
        // Helpers
        Route::post('/tickets/{id}/assign', [\App\Http\Controllers\Api\Crm\TicketController::class, 'assign']);
        Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\Api\Crm\TicketController::class, 'resolve']);
        Route::get('/tickets/{id}/analytics', [\App\Http\Controllers\Api\Crm\TicketController::class, 'analytics']);
        
        // CRM Analytics
        Route::get('/analytics/pipeline', [\App\Http\Controllers\Api\Crm\AgentAnalyticsController::class, 'pipeline']);
        Route::get('/analytics/stages', [\App\Http\Controllers\Api\Crm\AgentAnalyticsController::class, 'stages']);
        Route::get('/analytics/forecast', [\App\Http\Controllers\Api\Crm\AgentAnalyticsController::class, 'forecast']);
        Route::get('/analytics/performance', [\App\Http\Controllers\Api\Crm\AgentAnalyticsController::class, 'performance']);

        // Activities
        Route::get('/activities', [\App\Http\Controllers\Api\Crm\ActivityController::class, 'index']);
        Route::post('/activities', [\App\Http\Controllers\Api\Crm\ActivityController::class, 'store']);
        Route::get('/activities/{id}', [\App\Http\Controllers\Api\Crm\ActivityController::class, 'show']);
        Route::put('/activities/{id}', [\App\Http\Controllers\Api\Crm\ActivityController::class, 'update']);
        Route::delete('/activities/{id}', [\App\Http\Controllers\Api\Crm\ActivityController::class, 'destroy']);

        // Tasks
        Route::get('/tasks', [\App\Http\Controllers\Api\Crm\TaskController::class, 'index']);
        Route::post('/tasks', [\App\Http\Controllers\Api\Crm\TaskController::class, 'store']);
        Route::get('/tasks/{id}', [\App\Http\Controllers\Api\Crm\TaskController::class, 'show']);
        Route::put('/tasks/{id}', [\App\Http\Controllers\Api\Crm\TaskController::class, 'update']);
        Route::delete('/tasks/{id}', [\App\Http\Controllers\Api\Crm\TaskController::class, 'destroy']);

        // Meetings
        Route::get('/meetings', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'index']);
        Route::post('/meetings', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'store']);
        Route::get('/meetings/{id}', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'show']);
        Route::put('/meetings/{id}', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'update']);
        Route::delete('/meetings/{id}', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'destroy']);
        Route::delete('/meetings/{id}', [\App\Http\Controllers\Api\Crm\MeetingController::class, 'destroy']);

        // Pipelines & Stages
        Route::get('/pipelines', [\App\Http\Controllers\Api\Crm\PipelineController::class, 'index']);
        Route::post('/pipelines', [\App\Http\Controllers\Api\Crm\PipelineController::class, 'store']);
        Route::get('/pipelines/{id}', [\App\Http\Controllers\Api\Crm\PipelineController::class, 'show']);
        Route::put('/pipelines/{id}', [\App\Http\Controllers\Api\Crm\PipelineController::class, 'update']);
        Route::delete('/pipelines/{id}', [\App\Http\Controllers\Api\Crm\PipelineController::class, 'destroy']);
    });

    // ...

    // Buildings - Auth routes
    Route::post('/real-estate/buildings', [BuildingController::class, 'store']);
    Route::get('/real-estate/buildings/mine', [BuildingController::class, 'myBuildings']);
    Route::put('/real-estate/buildings/{building}', [BuildingController::class, 'update']);
    Route::delete('/real-estate/buildings/{building}', [BuildingController::class, 'destroy']);
    Route::get('/real-estate/buildings/{building}/analytics', [BuildingController::class, 'analytics']);
});

// Wildcard routes must come LAST
Route::get('/real-estate/{property}', [PropertyController::class, 'show']);
Route::post('/real-estate/{property}/contact', [PropertyController::class, 'sendContact']);



// Admin only routes (add middleware if you have one for admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/real-estate/categories', [CategoryController::class, 'store']);
    Route::put('/real-estate/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/real-estate/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/real-estate/plans', [PlanController::class, 'store']);
    Route::put('/real-estate/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('/real-estate/plans/{plan}', [PlanController::class, 'destroy']);
});

// Generic Media Routes (Auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{id}', [MediaController::class, 'show']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);
});

// Chat Routes (Auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/rooms', [\App\Http\Controllers\Api\Chat\ChatController::class, 'index']);
    Route::post('/chat/rooms', [\App\Http\Controllers\Api\Chat\ChatController::class, 'store']);
    Route::get('/chat/rooms/{id}/messages', [\App\Http\Controllers\Api\Chat\ChatController::class, 'messages']);
    Route::post('/chat/rooms/{id}/messages', [\App\Http\Controllers\Api\Chat\ChatController::class, 'sendMessage']);
});
