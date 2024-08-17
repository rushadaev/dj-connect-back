<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use App\Traits\UsesYooKassa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Models\DJ;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\UsesTelegram;
/**
 * @OA\Schema(
 *     schema="Payout",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="dj_id", type="integer"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="status", type="string", enum={"pending", "processed"}),
 *     @OA\Property(property="payout_type", type="string", enum={"bank_card", "sbp", "yoo_money"}),
 *     @OA\Property(property="payout_details", type="string"),
 *     @OA\Property(property="yookassa_payout_id", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="processed_at", type="string", format="date-time", nullable=true)
 * )
 */
class PayoutController extends Controller
{
    use UsesYooKassa;
    use UsesTelegram;

    public function __construct()
    {
        $this->initializeYooKassa();
    }

    /**
 * @OA\Post(
 *     path="/payouts",
 *     summary="Create a payout request",
 *     description="Allows a DJ to create a payout request in the database.",
 *     tags={"Payouts"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="dj_id", type="integer", description="ID of the DJ"),
 *             @OA\Property(property="amount", type="number", format="float", description="Amount to be paid out"),
 *             @OA\Property(property="payout_type", type="string", enum={"bank_card", "sbp", "yoo_money"}, description="Type of payout"),
 *             @OA\Property(
 *                 property="payout_details",
 *                 oneOf={
 *                     @OA\Schema(
 *                         type="object",
 *                         @OA\Property(property="card_number", type="string", description="Bank card number, required if payout_type is bank_card")
 *                     ),
 *                     @OA\Schema(
 *                         type="object",
 *                         @OA\Property(property="bank_id", type="string", description="Bank ID, required if payout_type is sbp"),
 *                         @OA\Property(property="phone", type="string", description="Phone number, required if payout_type is sbp")
 *                     ),
 *                     @OA\Schema(
 *                         type="object",
 *                         @OA\Property(property="account_number", type="string", description="YooMoney account number, required if payout_type is yoo_money")
 *                     )
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payout request created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Payout")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
    public function createPayoutRequest(Request $request)
    {
        $validated = $request->validate([
            'dj_id' => 'required|exists:djs,id',
            'amount' => 'required|numeric',
            'payout_type' => 'required|in:bank_card,sbp,yoo_money',
            'payout_details' => 'required|array',
        ]);

        // Create the payout request in the database
        $payout = Payout::create([
            'dj_id' => $validated['dj_id'],
            'amount' => $validated['amount'],
            'status' => 'pending', // Initially set the status to pending
            'payout_type' => $validated['payout_type'],
            'payout_details' => json_encode($validated['payout_details']),
        ]);

        return response()->json($payout);
    }

    /**
     * @OA\Post(
     *     path="/payouts/{payoutId}/approve",
     *     summary="Approve and send a payout",
     *     description="Allows an admin to approve a payout request and send it via YooKassa.",
     *     tags={"Payouts"},
     *      security={{"telegramAuth":{}}},
     *     @OA\Parameter(
     *         name="payoutId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the payout to be approved"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payout processed successfully",
      * @OA\Schema(
 *     schema="PayoutSchema",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="dj_id", type="integer"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="status", type="string", enum={"pending", "processed"}),
 *     @OA\Property(property="payout_type", type="string", enum={"bank_card", "sbp", "yoo_money"}),
 *     @OA\Property(property="payout_details", type="string"),
 *     @OA\Property(property="yookassa_payout_id", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="processed_at", type="string", format="date-time", nullable=true)
 * )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid payout ID or payout already processed",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function approveAndSendPayout($payoutId)
    {
        // Find the payout by ID
        $payout = Payout::findOrFail($payoutId);

        // Check if the payout is still pending
        if ($payout->status !== 'pending') {
            return response()->json(['error' => 'Payout has already been processed or is not pending.'], 400);
        }

        // Prepare the data for YooKassa payout
        $amount = [
            'value' => number_format($payout->amount, 2, '.', ''),
            'currency' => 'RUB',
        ];

        $description = "Выплата по заказу №{$payout->id}";
        $payoutDestination = $this->getPayoutDestinationData($payout->payout_type, json_decode($payout->payout_details, true));

        // Create the payout in YooKassa
        $yookassaPayout = $this->yooKassaService->createPayout($amount, $payoutDestination, $description);

        // Update the payout with the YooKassa ID and status
        $payout->update([
            'yookassa_payout_id' => $yookassaPayout['id'],
            'status' => $yookassaPayout['status'] === 'succeeded' ? 'processed' : 'pending',
            'processed_at' => now(),
        ]);

        return response()->json($payout);
    }

    private function getPayoutDestinationData(string $type, array $details): array
    {
        switch ($type) {
            case 'bank_card':
                return [
                    'type' => 'bank_card',
                    'card' => [
                        'number' => $details['card_number'],
                    ],
                ];
            case 'sbp':
                return [
                    'type' => 'sbp',
                    'bank_id' => $details['bank_id'],
                    'phone' => $details['phone'],
                ];
            case 'yoo_money':
                return [
                    'type' => 'yoo_money',
                    'account_number' => $details['account_number'],
                ];
            default:
                throw new \InvalidArgumentException('Invalid payout type');
        }
    }

    /**
     * @OA\Get(
     *     path="/sbp-participants",
     *     summary="Get list of SBP participants",
     *     description="Retrieves a list of banks and payment services connected to the SBP.",
     *     tags={"Payouts"},
     *     @OA\Response(
     *         response=200,
     *         description="List of SBP participants retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="bank_id", type="string", description="Identifier of the SBP participant"),
     *                 @OA\Property(property="name", type="string", description="Name of the SBP participant")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving SBP participants",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getSbpParticipants()
    {
        try {
            $result = $this->yooKassaService->getSbpBanks();
            $participants = [];

            //Mock data
            foreach ($result->items as $item) {
                $participants[] = [
                    'bank_id' => $item->id,
                    'name' => $item->name,
                    'bic' => $item->bic,
                ];
            }
            // foreach ($result->getItems() as $item) {
            //     $participants[] = [
            //         'bank_id' => $item->getId(),
            //         'name' => $item->getName(),
            //     ];
            // }

            return response()->json($participants);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve SBP participants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/payouts/{dj_id}",
     *     summary="Get all payouts and available balance for a specific DJ",
     *     description="Fetches all payouts and the available balance associated with a specific DJ from the database.",
     *     tags={"Payouts"},
     *     @OA\Parameter(
     *         name="dj_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the DJ"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payouts and available balance for the DJ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="available_balance",
     *                 type="number",
     *                 format="float",
     *                 description="Sum of paid orders"
     *             ),
     *             @OA\Property(
     *                 property="payouts",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Payout")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="DJ not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getAllPayouts($dj_id)
    {
        $dj = DJ::find($dj_id);

        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        // Calculate the total sum of paid orders
        $total_paid_orders = Order::where('dj_id', $dj_id)
                                ->whereHas('transactions', function ($query) {
                                    $query->where('status', Transaction::STATUS_PAID);
                                })
                                ->sum('price');

        // Calculate the total sum of proceeded payouts
        $total_proceeded_payouts = Payout::where('dj_id', $dj_id)
                                        ->where('status', Payout::STATUS_PROCESSED)
                                        ->sum('amount');

        // Calculate the available balance by deducting the proceeded payouts
        $available_balance = round($total_paid_orders - $total_proceeded_payouts, 2);

        // Fetch all payouts from the database
        $payouts = Payout::where('dj_id', $dj_id)->get();

        // Return the available balance and the payouts as a JSON response
        return response()->json([
            'available_balance' => $available_balance,
            'payouts' => $payouts
        ]);
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


            $track = $order->track;
            $user = $order->user;
            $dj = $order->dj;
            $userTelegramId = $user->telegram_id;
            $djTelegramId = $dj->telegram_id;
            $telegram = $this->useTelegram();

            $message = "\nDJ: {$dj->stage_name}\nТрек: {$track->name}\nЦена: {$order->price}\nСообщение: {$order->message}";

            $webAppDirectUrl = config('webapp.direct_url');
            $tgWebAppUrl = "{$webAppDirectUrl}?startapp=order_{$orderId}";

            // User Inline Keyboard with payment link
            $userKeyboard = new InlineKeyboardMarkup([
                [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrl]],
            ]);

            if ($userTelegramId) {
                $telegram->sendMessage($userTelegramId, "🎉 #заказ_{$order->id} оплачен, ожидайте ваш трек в течение 15 минут:{$message}", null, false, null, $userKeyboard);
            }

            if ($djTelegramId) {
                $telegram->sendMessage($djTelegramId, "🎧#заказ_{$order->id} оплачен! Поставьте трек в течение 15 минут: {$message}", null, false, null, $userKeyboard);
            }

            return response()->json(['message' => 'Payment successful']);
        } else {
            // Handle payment failure
            return response()->json(['message' => 'Payment failed']);
        }
    }
}
