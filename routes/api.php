<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\VisibilityController;
use App\Http\Controllers\Api\OriginController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ResourceInteractionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserListController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('Authorized')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Routes nécessitant uniquement l'authentification
Route::middleware('Authorized')->group(function () {
    // READ operations - get resources
    Route::get('resources', [ResourceController::class, 'index']);
    Route::get('resources/{resource}', [ResourceController::class, 'show']);
    Route::get('resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');
    
    // Other read operations for reference data
    Route::apiResource('types', TypeController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('visibilities', VisibilityController::class)->only(['index', 'show']);
    Route::apiResource('origins', OriginController::class)->only(['index', 'show']);
    
    // User interactions
    Route::apiResource('comments', CommentController::class);
    Route::apiResource('invitations', InvitationController::class);
    Route::apiResource('messages', MessageController::class);
    Route::apiResource('resource-interactions', ResourceInteractionController::class);
    Route::get('messages/conversations', [MessageController::class, 'conversations']);
    Route::post('messages/mark-all-read', [MessageController::class, 'markAllAsRead']);

    // User management - get a list of users
    Route::get('/users/list', [UserListController::class, 'index']);
    Route::get('/users/search', [UserListController::class, 'search']);

    // CREATE operations for resources - authenticated users can create
    Route::post('resources', [ResourceController::class, 'store']);
});

// Routes nécessitant des privilèges admin
Route::middleware(['AuthorizedAdmin'])->group(function () {
    // Admin operations for resources
    Route::put('resources/{resource}', [ResourceController::class, 'update']);
    Route::patch('resources/{resource}', [ResourceController::class, 'update']);
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy']);
    
    // Admin operations for reference data
    Route::apiResource('types', TypeController::class)->except(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::apiResource('visibilities', VisibilityController::class)->except(['index', 'show']);
    Route::apiResource('origins', OriginController::class)->except(['index', 'show']);
});