<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

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

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::get('/profile/{userId}', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/photo', [UserController::class, 'uploadPhoto']);
    
    // Matching
    Route::get('/potential-matches', [UserController::class, 'getPotentialMatches']);
    Route::post('/like/{user}', [UserController::class, 'likeUser']);
    Route::post('/pass/{user}', [UserController::class, 'passUser']);
    Route::get('/matches', [UserController::class, 'getMatches']);
    
    // Conversations
    Route::get('/conversations', [UserController::class, 'getConversations']);
    Route::post('/conversations', [UserController::class, 'createConversation']);
    Route::get('/conversations/{conversation}/messages', [UserController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/messages', [UserController::class, 'sendMessage']);
    
    // Location
    Route::post('/location', [UserController::class, 'updateLocation']);
    
    // Posts
    Route::get('/posts', [\App\Http\Controllers\PostController::class, 'index']);
    Route::post('/posts', [\App\Http\Controllers\PostController::class, 'store']);
    Route::post('/posts/{post}/like', [\App\Http\Controllers\PostController::class, 'like']);
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});
