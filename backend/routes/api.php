<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\TransactionController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('games', GameController::class)->only(['store', 'show']);
    Route::post('/games/{game}/join', [GameController::class, 'join']);
    Route::post('/games/{game}/add-funds', [GameController::class, 'addFunds']);
    Route::post('/games/{game}/rounds', [GameController::class, 'startRound']);
    Route::post('/games/{game}/rounds/{round}/settle', [GameController::class, 'settleRound']);

    Route::post('/rounds/{round}/bet', [BettingController::class, 'bet']);
    Route::post('/rounds/{round}/call', [BettingController::class, 'call']);
    Route::post('/rounds/{round}/check', [BettingController::class, 'check']);
    Route::post('/rounds/{round}/fold', [BettingController::class, 'fold']);
    Route::get('/rounds/{round}/call-amount', [BettingController::class, 'callAmount']);

    Route::get('/games/{game}/transactions', [TransactionController::class, 'index']);
});
