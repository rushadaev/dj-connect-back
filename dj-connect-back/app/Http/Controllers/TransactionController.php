<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     required={"order_id", "amount", "status", "payment_url"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Transaction ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="order_id",
 *         type="integer",
 *         description="Order ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Transaction amount",
 *         example=19.99
 *     ),
 *     @OA\Property(
 *         property="payment_url",
 *         type="string",
 *         description="Payment URL",
 *         example="https://payment.gateway.com/pay?order_id=1"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Transaction status",
 *         enum={"pending", "paid", "cancelled"},
 *         example="pending"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-07-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-07-01T00:00:00Z"
 *     )
 * )
 */
class TransactionController extends Controller
{

    /**
     * @OA\Patch(
     *      path="/transactions/{transaction_id}/mark-paid",
     *      operationId="markTransactionPaid",
     *      tags={"Transaction"},
     *      summary="Mark a transaction as paid",
     *      description="Allows a DJ to mark a transaction as paid",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="transaction_id",
     *          description="Transaction ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Transaction marked as paid",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Transaction marked as paid")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Transaction not found")
     * )
     */
    public function markTransactionPaid($transaction_id)
    {
        $transaction = Transaction::find($transaction_id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $transaction->status = Transaction::STATUS_PAID;
        $transaction->save();

        return response()->json(['success' => true, 'message' => 'Transaction marked as paid']);
    }

    /**
     * @OA\Patch(
     *      path="/transactions/{transaction_id}/cancel",
     *      operationId="cancelTransaction",
     *      tags={"Transaction"},
     *      summary="Cancel a transaction",
     *      description="Allows a DJ to cancel a pending transaction",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="transaction_id",
     *          description="Transaction ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Transaction cancelled successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Transaction cancelled")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Transaction not found")
     * )
     */
    public function cancelTransaction($transaction_id)
    {
        $transaction = Transaction::find($transaction_id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        try {
            $transaction->cancel();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Transaction cancelled']);
    }
}
