<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rota de teste
Route::get('/metadata', function () {
    return [
        "name" => "DAD 2025/26 Worksheet API",
        "version" => "0.0.1"
    ];
});

// Rotas de autenticação
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);
    Route::delete('/profile', [AuthController::class, 'deleteAccount']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
