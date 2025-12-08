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
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\RequirementController;
use App\Http\Controllers\Api\ProposalController;
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
Route::get('/real-estate/{property}', [PropertyController::class, 'show']);
Route::get('/real-estate/categories', [CategoryController::class, 'index']);
Route::get('/real-estate/plans', [PlanController::class, 'index']);
Route::post('/real-estate/{property}/contact', [PropertyController::class, 'sendContact']);

// Buildings - Public routes
Route::get('/buildings', [BuildingController::class, 'index']);
Route::get('/buildings/{building}', [BuildingController::class, 'show']);

// Real Estate API - Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/real-estate', [PropertyController::class, 'store']);
    Route::put('/real-estate/{property}', [PropertyController::class, 'update']);
    Route::delete('/real-estate/{property}', [PropertyController::class, 'destroy']);

    Route::post('/real-estate/{property}/favorite', [PropertyController::class, 'toggleFavorite']);
    Route::get('/user/favorites', [FavoriteController::class, 'list']);

    // Agent Profile
    Route::get('/agent/profile', [AgentController::class, 'show']);
    Route::put('/agent/profile', [AgentController::class, 'update']);
    Route::get('/agent/stats', [AgentController::class, 'stats']);

    // Clients
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    // Leads
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    Route::put('/leads/{lead}', [LeadController::class, 'update']);
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);
    Route::post('/leads/{lead}/convert-to-client', [LeadController::class, 'convertToClient']);

    // Activities
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::get('/activities/{activity}', [ActivityController::class, 'show']);
    Route::put('/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);
    Route::post('/activities/{activity}/complete', [ActivityController::class, 'complete']);

    // Requirements
    Route::get('/requirements', [RequirementController::class, 'index']);
    Route::post('/requirements', [RequirementController::class, 'store']);
    Route::get('/requirements/{requirement}', [RequirementController::class, 'show']);
    Route::put('/requirements/{requirement}', [RequirementController::class, 'update']);
    Route::delete('/requirements/{requirement}', [RequirementController::class, 'destroy']);

    // Proposals
    Route::get('/proposals', [ProposalController::class, 'index']);
    Route::post('/proposals', [ProposalController::class, 'store']);
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show']);
    Route::put('/proposals/{proposal}', [ProposalController::class, 'update']);
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy']);

    // Buildings - Auth routes
    Route::post('/buildings', [BuildingController::class, 'store']);
    Route::put('/buildings/{building}', [BuildingController::class, 'update']);
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy']);
});

// Public proposal sharing
Route::get('/shared/proposals/{token}', [ProposalController::class, 'showByToken']);

// Admin only routes (add middleware if you have one for admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/real-estate/categories', [CategoryController::class, 'store']);
    Route::put('/real-estate/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/real-estate/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/real-estate/plans', [PlanController::class, 'store']);
    Route::put('/real-estate/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('/real-estate/plans/{plan}', [PlanController::class, 'destroy']);
});
