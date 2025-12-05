<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    // Perfil do User (GET, PUT, DELETE)
    Route::get('/users/me', [UserController::class, 'show']);
    Route::put('/users/me', [UserController::class, 'update']); // Editar perfil
    Route::patch('/users/me', [UserController::class, 'update']); // (Opcional) compatibilidade
    Route::delete('/users/me', [UserController::class, 'destroy']); // Apagar conta


    Route::post('logout', [AuthController::class, 'logout']);
});

Route::apiResource('games', GameController::class);
