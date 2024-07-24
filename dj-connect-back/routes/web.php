<?php
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\DJController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/telegram', [TelegramController::class, 'handleWebhook']);
Route::get('/dj/{dj_id}/qr-code', [DJController::class, 'generateQRCode']);