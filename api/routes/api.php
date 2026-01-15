<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\CoinPurchaseController;
use App\Http\Controllers\GameHistoryController;
use App\Http\Controllers\StatisticsController;
use App\Http\Middleware\EnsureUserIsAdmin;

// --- ROTAS PÚBLICAS ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/leaderboards/games', [GameHistoryController::class, 'leaderboardGames']); // G4
Route::get('/statistics/public', [StatisticsController::class, 'getPublicStats']);     // G6

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/games/single/start', [GameController::class, 'start']);
    Route::post('/games/single/play', [GameController::class, 'play']);
});



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

// Rotas protegidas por Autenticação E Admin
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {

    // Gestão de Utilizadores
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}/block', [UserController::class, 'block']);
    Route::patch('/users/{user}/unblock', [UserController::class, 'unblock']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('admin/statistics', [StatisticsController::class, 'getAdminStats']);
    Route::get('admin/transactions', [StatisticsController::class, 'getAdminTransactions']);
});
