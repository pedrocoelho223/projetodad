<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\CoinPurchaseController;
use App\Http\Controllers\GameHistoryController;
use App\Http\Controllers\StatisticsController;

// --- ROTAS PÚBLICAS ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/leaderboards/games', [GameHistoryController::class, 'leaderboardGames']); // G4
Route::get('/statistics/public', [StatisticsController::class, 'getPublicStats']);     // G6

// --- ROTAS PROTEGIDAS ---
Route::middleware('auth:sanctum')->group(function () {

    

    // Perfil
    Route::get('/users/me', [UserController::class, 'show']);
    Route::match(['put', 'patch'], '/users/me', [UserController::class, 'update']);
    Route::delete('/users/me', [UserController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Moedas
    Route::post('coins/purchase', [CoinPurchaseController::class, 'purchase']);
    Route::get('coins/transactions', [CoinController::class, 'transactions']);

    // Histórico pessoal
    Route::get('my/games', [GameHistoryController::class, 'myGames']);

    // --- ADMIN ---
    Route::middleware('admin')->group(function () {
        Route::get('admin/statistics', [StatisticsController::class, 'getAdminStats']);
        Route::get('admin/transactions', [StatisticsController::class, 'getAdminTransactions']);
    });
});
