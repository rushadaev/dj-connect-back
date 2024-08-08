<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use App\Traits\UsesYooKassa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Transaction;

class PayoutController extends Controller
{
    use UsesYooKassa;

    public function __construct()
    {
        $this->initializeYooKassa();
    }
    public function create(Request $request)
    {
        $validated = $request->validate([
            'dj_id' => 'required|exists:djs,id',
            'amount' => 'required|numeric',
            'status' => 'required|in:pending,processed',
        ]);

        $payout = Payout::create($validated);

        return response()->json($payout);
    }

    public function updateStatus(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processed',
        ]);

        $payout->update($validated);

        return response()->json($payout);
    }

    public function getPayouts()
    {
        $payouts = Payout::all();

        $amount = '123.45';
        $orderId = '69';
        $description = 'Test payment for order';
        try {
            $url = $this->yooKassaService->createPaymentLink($amount, $orderId, $description);
        } catch (\Exception $e) {
            $url = $e;
            Log::error($e->getMessage());
        }
        return response()->json(['url' => $url]);
    }

    public function paymentReturn(Request $request)
    {
        $orderId = $request->orderId;
        // Retrieve payment ID from cache
        $paymentId = Cache::get("payment_id_{$orderId}");
    
        if (!$paymentId) {
            return view('payment.failure', ['orderId' => $orderId, 'message' => 'Invalid or expired payment ID']);
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
            return response()->json(['message' => 'Payment successful']);
        } else {
            // Handle payment failure
            return response()->json(['message' => 'Payment failed']);
        }
    }
}
