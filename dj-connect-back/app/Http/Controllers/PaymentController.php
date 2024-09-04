<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\YooKassaService; // Assuming you have a service for YooKassa
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Models\DJ;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\UsesTelegram;
use App\Events\OrderUpdated;

class PaymentController extends Controller
{
    use UsesYooKassa;
    use UsesTelegram;

    public function __construct()
    {
        $this->initializeYooKassa();
    }

    public function generatePaymentLink(Request $request)
    {
        $amount = $request->input('amount');
        $orderId = $request->input('orderId');
        $description = 'Test payment for order';

        try {
            // Generate payment link using YooKassa service
            $paymentLink = $this->yooKassaService->createPaymentLink($amount, $orderId, $description);

            // Optionally log or perform other actions here
            return redirect($paymentLink); // Redirect to the actual payment link
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Unable to generate payment link'], 500);
        }
    }

    public function paymentReturn(Request $request)
    {
        $orderId = $request->orderId;
        // Retrieve payment ID from cache
        $paymentId = Cache::get("payment_id_{$orderId}");
    
        if (!$paymentId) {
            // return view('payment.failure', ['orderId' => $orderId, 'message' => 'Invalid or expired payment ID']);
            return;
        }
    
        // Retrieve payment details from YooKassa
        $payment = $this->yooKassaService->retrievePayment($paymentId);
    
        $orderId = $payment->getMetadata()->order_id ?? null;

        Log::info('Payment details', $payment->jsonSerialize());
        if ($payment->status === 'succeeded') {
            $order = Order::find($orderId);
            
            // Retrieve the last transaction and mark it as paid
            $lastTransaction = $order->transactions->last();
            if ($lastTransaction) {
                $lastTransaction->status = Transaction::STATUS_PAID;
                $lastTransaction->save();
            }

            event(new OrderUpdated($order));

            $track = $order->track;
            $user = $order->user;
            $dj = $order->dj;
            $userTelegramId = $user->telegram_id;
            $djTelegramId = $dj->telegram_id;
            $telegram = $this->useTelegram();

            $message = "\nDJ: {$dj->stage_name}\nТрек: {$track->name}\nЦена: {$order->price}\nСообщение: {$order->message}";

            $webAppDirectUrl = config('webapp.direct_url');
            $webAppDirectUrlDj = config('webapp.direct_url_dj'); 
            $tgWebAppUrl = "{$webAppDirectUrl}?startapp=order_{$order->id}";
            $tgWebAppUrlDj = "{$webAppDirectUrlDj}?startapp=order_{$order->id}";

            // User Inline Keyboard with payment link
            $userKeyboard = new InlineKeyboardMarkup([
                [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrl]],
            ]);

            if ($userTelegramId) {
                $telegram->notifyUser($userTelegramId, "🎉 #заказ_{$order->id} оплачен, ожидайте ваш трек в течение 15 минут:{$message}", null, false, null, $userKeyboard);
            }

            $djKeyboard = new InlineKeyboardMarkup([
                [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrlDj]],
            ]);

            if ($djTelegramId) {
                $telegram->notifyDj($djTelegramId, "🎧#заказ_{$order->id} оплачен! Поставьте трек в течение 15 минут: {$message}", null, false, null, $djKeyboard);
            }

            return response()->json(['message' => 'Payment successful']);
        } else {
            // Handle payment failure
            return response()->json(['message' => 'Payment failed']);
        }
    }
}