<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\SubTaskController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\planController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function(){
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::get('/oauth/google', [AuthController::class, 'oAuthUrl']);
            Route::get('/oauth/google/callback', [AuthController::class, 'oAuthCallback']);

            Route::middleware('auth:sanctum')->group(function(){
                Route::get('/me', [AuthController::class, 'me']);
                Route::get('/logout', [AuthController::class, 'logout']);
            });
        });

        Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/me', [UserController::class, 'me']);
        Route::post('/user/update', [UserController::class, 'update']);
        Route::delete('/user/delete-avatar', [UserController::class, 'deleteAvatar']);
        Route::post('/user/change-password', [UserController::class, 'changePassword']);
  });

    Route::get('plans', [PlanController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::apiResource('tasks', TaskController::class)->only(['index', 'store','show', 'destroy']);
        Route::post('tasks/{id}', [TaskController::class, 'update']);
        Route::apiResource('subtasks', SubTaskController::class)->only(['index', 'store','destroy']);
        Route::post('subtasks/{id}', [SubTaskController::class, 'update']);
        Route::post('subtasks', [SubTaskController::class, 'changeStatus']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'store','show', 'destroy']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store','show']);
    });
    Route::post('/payments/callback', [PaymentController::class, 'callback']);
});
