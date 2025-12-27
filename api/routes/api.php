<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\CoinPurchaseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WsServiceController;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users/me', [UserController::class, 'show']);
    Route::put('/users/me', [UserController::class, 'update']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::delete('/users/me', [UserController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Coins
    Route::get('/coins/balance', [CoinController::class, 'balance']);
    Route::post('/coins/purchase', [CoinPurchaseController::class, 'purchase']);

    // GAMES protegidos
    Route::apiResource('games', GameController::class);

    // Single-player: jogar carta
    Route::post('/games/{game}/play', [GameController::class, 'play']);
});

Route::middleware('ws.service')->prefix('ws')->group(function () {
    Route::post('/multiplayer/game/create', [WsServiceController::class, 'createMultiplayerGame']);
    Route::post('/multiplayer/match/create', [WsServiceController::class, 'createMatch']);
    Route::post('/multiplayer/game/end', [WsServiceController::class, 'endGame']);
    Route::post('/multiplayer/match/end', [WsServiceController::class, 'endMatch']);
});


//Permitir anonimo no single-player
 Route::apiResource('games', GameController::class)->only(['index','show']);
 Route::post('/games', [GameController::class, 'store']);
 Route::post('/games/{game}/play', [GameController::class, 'play']);
