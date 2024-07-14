<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\DJController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PayoutController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(['telegram.auth'])->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/profile/{user}', [UserController::class, 'profile']);

    Route::post('/dj/register', [DJController::class, 'register']);
    Route::get('/dj/profile/{dj}', [DJController::class, 'profile']);
    Route::put('/dj/profile/{dj}', [DJController::class, 'updateProfile']);

    Route::post('/order', [OrderController::class, 'create']);
    Route::put('/order/status/{order}', [OrderController::class, 'updateStatus']);
    Route::get('/order/history/{user}', [OrderController::class, 'history']);

    Route::post('/track', [TrackController::class, 'create']);
    Route::put('/track/{track}', [TrackController::class, 'update']);

    Route::post('/transaction', [TransactionController::class, 'create']);
    Route::put('/transaction/status/{transaction}', [TransactionController::class, 'updateStatus']);

    Route::post('/payout', [PayoutController::class, 'create']);
    Route::put('/payout/status/{payout}', [PayoutController::class, 'updateStatus']);

    Route::get('/example', function (Request $request) {
        return $request->user;
        return response()->json(['message' => 'This is an example route']);
    });
});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

