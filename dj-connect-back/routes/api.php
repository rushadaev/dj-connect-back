<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\DJController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PayoutController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(['telegram.auth'])->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/profile/me', [UserController::class, 'getMe']);
    Route::get('/profile/{telegram_id}', [UserController::class, 'profile']);
    
    Route::post('/dj/register', [DJController::class, 'register']);
    Route::get('/dj/profile/{dj}', [DJController::class, 'profile']);
    Route::put('/dj/profile/{dj}', [DJController::class, 'updateProfile']);

    Route::delete('/dj/clear', [DJController::class, 'clearDJs']);
    
    Route::post('/dj/{dj_id}/track', [DJController::class, 'addTrack']);
    Route::get('/dj/{dj_id}/tracks', [DJController::class, 'getTracks']);
    Route::patch('/dj/{dj_id}/track/{track_id}/price', [DJController::class, 'updateTrackPrice']);
    Route::get('/dj/{dj_id}/statistics', [DJController::class, 'getStatistics']);

    Route::get('/user/orders', [OrderController::class, 'getOrdersForUser']);
    Route::get('/dj/{dj_id}/orders', [OrderController::class, 'getOrdersForDJ']);

    Route::post('/orders', [OrderController::class, 'createOrder']);
    Route::patch('/orders/{order_id}/accept', [OrderController::class, 'acceptOrder']);
    Route::patch('/orders/{order_id}/decline', [OrderController::class, 'declineOrder']);
    Route::patch('/orders/{order_id}/cancel', [OrderController::class, 'cancelOrder']);

    Route::patch('/transactions/{transaction_id}/mark-paid', [TransactionController::class, 'markTransactionPaid']);
    Route::patch('/transactions/{transaction_id}/cancel', [TransactionController::class, 'cancelTransaction']);

    Route::post('/payout', [PayoutController::class, 'create']);
    Route::put('/payout/status/{payout}', [PayoutController::class, 'updateStatus']);

    Route::get('/text', function (Request $request) {
        return $request->user;
        return response()->json(['message' => 'This is an test route']);
    });
});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

