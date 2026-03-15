<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api-passport')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);

    Route::get('/users-read-test', function (Request $request) {
        return response()->json([
            'message' => 'El token con el alcance users:read es valido',
            'user' => $request->user(),
        ]);
    })->middleware(CheckTokenForAnyScope::using('users:read'));

    Route::post('/users-create-test', function (Request $request) {
        return response()->json([
            'message' => 'El token con el alcance users:create es valido',
            'user' => $request->user(),
        ]);
    })->middleware(CheckToken::using('users:create'));
});