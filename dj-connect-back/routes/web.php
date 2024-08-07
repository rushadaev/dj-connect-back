<?php
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\DJController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayoutController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/telegram', [TelegramController::class, 'handleWebhook']);
Route::get('/dj/{dj_id}/qr-code', [DJController::class, 'generateQRCode']);

Route::get('/payment/return', [PayoutController::class, 'paymentReturn'])->name('payment.return');