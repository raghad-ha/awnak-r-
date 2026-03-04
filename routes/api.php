<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JoinRequestController;
use App\Http\Controllers\Api\V1\Admin\JoinRequestAdminController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
// User (volunteer/org)
    Route::post('/join-requests', [JoinRequestController::class, 'store']);
    Route::get('/join-requests/me', [JoinRequestController::class, 'myRequest']);

    // Admin (Approval Officer)
    Route::get('/admin/join-requests', [JoinRequestAdminController::class, 'index']);
    Route::get('/admin/join-requests/{id}', [JoinRequestAdminController::class, 'show']);
    Route::post('/admin/join-requests/{id}/approve', [JoinRequestAdminController::class, 'approve']);
    Route::post('/admin/join-requests/{id}/reject', [JoinRequestAdminController::class, 'reject']);
    });



});
