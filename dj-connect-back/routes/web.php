<?php
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\DJController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/telegram', [TelegramController::class, 'handleWebhook']);
Route::get('/dj/{dj_id}/qr-code', [DJController::class, 'generateQRCode']);

Route::get('/generate-payment-link', [PaymentController::class, 'generatePaymentLink'])->name('generate.payment.link');

Route::get('/payment/return', [PaymentController::class, 'paymentReturn'])->name('payment.return');
Route::post('/webhook/payment/success', [PaymentController::class, 'paymentReturn'])->name('payment.success');


Route::get('/order-updates/{order_id}', [OrderController::class, 'streamUpdates']);

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () {
    // your CRUD resources and other admin routes here
    // example:
    // Route::crud('article', 'ArticleCrudController');
});


use App\Events\OrderUpdated;
use App\Events\OrderCreated;

Route::get('/test-broadcast', function () {
    $order = ['id' => 1, 'status' => 'updated'];
    event(new OrderCreated($order));
    return 'Event Broadcasted';
});