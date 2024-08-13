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
    Route::post('/admin/set', [UserController::class, 'setAdmin']);
    
    Route::post('/dj/register', [DJController::class, 'register']);
    Route::get('/dj/profile/{dj}', [DJController::class, 'profile']);
    Route::put('/dj/profile/{dj}', [DJController::class, 'updateProfile']);

    Route::delete('/dj/clear', [DJController::class, 'clearDJs']);
    
    Route::post('/dj/{dj_id}/track', [DJController::class, 'addTrack']);
    Route::put('/dj/{dj_id}/track/{track_id}', [DJController::class, 'updateTrack']);
    Route::delete('/dj/{dj_id}/track/{track_id}', [DJController::class, 'deleteTrack']);

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

    // Route for DJs to create a payout request
    Route::post('/payouts', [PayoutController::class, 'createPayoutRequest']);

    // Route for admin to approve and send the payout
    Route::post('/payouts/{payoutId}/approve', [PayoutController::class, 'approveAndSendPayout']);

    Route::get('/sbp-participants', [PayoutController::class, 'getSbpParticipants']);

    Route::get('/payouts/{dj_id}', [PayoutController::class, 'getAllPayouts']);

    Route::get('/text', function (Request $request) {
        return $request->user;
        return response()->json(['message' => 'This is an test route']);
    });
});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

