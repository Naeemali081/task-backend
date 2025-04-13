<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    return response()->json([
        'success' => true,
        'message' => 'Besa Health API'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/google', [GoogleAuthController::class, 'handleGoogleToken']);
Route::post('reset-password', [AuthController::class, 'sendResetLinkEmail']);

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user(); 
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::apiResource('tasks', TaskController::class);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::get('tasks/{task}/download', [TaskController::class, 'download']);
});


