<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;

use Illuminate\Http\Request;

use App\Http\Controllers\CoinController;
use App\Http\Controllers\CoinPurchaseController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WsServiceController;

use App\Http\Controllers\LeaderboardController;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users/me', [UserController::class, 'show']);
    Route::put('/users/me', [UserController::class, 'update']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::delete('/users/me', [UserController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Coins
    // Tem de ser 'purchase' e não 'buy'
Route::post('coins/purchase', [CoinPurchaseController::class, 'purchase']);

    // Rota para VER SALDO
    Route::get('/coins/balance', [CoinController::class, 'balance']);

    // Rota para VER HISTÓRICO DE COMPRAS
    Route::get('coins/transactions', [CoinController::class, 'transactions']);

    // GAMES protegidos
    Route::apiResource('games', GameController::class);

    // Leaderboard
Route::get('/leaderboard/top', [GameController::class, 'index']);


});

// Rotas públicas para Jogo Single Player
Route::post('games/single/start', [GameController::class, 'start']);
Route::post('games/single/play', [GameController::class, 'play']);
