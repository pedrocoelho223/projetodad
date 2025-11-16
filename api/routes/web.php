<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/user/profile-information', [ProfileController::class, 'update'])->name('profile.update');

    // Rota de remoção
    Route::delete('/user/delete-account', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
