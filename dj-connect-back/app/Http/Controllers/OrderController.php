<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Track;
use App\Models\DJ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\UsesTelegram;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Illuminate\Support\Facades\Log;
use App\Traits\UsesYooKassa;
use App\Events\OrderUpdated;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     title="Order",
 *     required={"user_id", "dj_id", "track_id", "price", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Order ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="User ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="dj_id",
 *         type="integer",
 *         description="DJ ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="track_id",
 *         type="integer",
 *         description="Track ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         description="Price of the order",
 *         example=19.99
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Message for the order",
 *         example="Please play this track!"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Status of the order",
 *         enum={"pending", "accepted", "declined", "price_changed"},
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
 *     ),
 *     @OA\Property(
 *         property="is_paid",
 *         type="boolean",
 *         description="Is the order paid based on the transaction status",
 *         example="false"
 *      ),
 *     @OA\Property(
 *         property="transactions",
 *         type="array",
 *         description="Array of transaction details",
 *         @OA\Items(
 *             ref="#/components/schemas/Transaction"
 *         )
 *      )
 * )
 */
class OrderController extends Controller
{
    use UsesTelegram;
    use UsesYooKassa;


    public function __construct()
    {
        $this->initializeYooKassa();
    }
    /**
     * @OA\Post(
     *      path="/orders",
     *      operationId="createOrder",
     *      tags={"Order"},
     *      summary="Create an order",
     *      description="Allows a user to create an order for a track",
     *      security={{"telegramAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="dj_id", type="integer", example=1),
     *              @OA\Property(property="track_id", type="integer", example=1),
     *              @OA\Property(property="custom_track", type="string", example="My Custom Track"),
     *              @OA\Property(property="price", type="number", format="float", example=19.99),
     *              @OA\Property(property="message", type="string", example="Please play this track!")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Order")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ or Track not found")
     * )
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'dj_id' => 'required|exists:djs,id',
            'track_id' => 'nullable|exists:tracks,id',
            'custom_track' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'message' => 'nullable|string|max:255',
            'timezone' => 'nullable|string'
        ]);

        $dj = DJ::find($validated['dj_id']);

        //Creating new track
        $trackName = $validated['custom_track'] ?? null;
        if ($trackName) {
            // Check if a track with the same name already exists for this DJ
            $existingTrack = Track::where('name', $trackName)->first();
        
            if ($existingTrack) {
                $validated['track_id'] = $existingTrack->id;
            } else {
                // Create the new track
                $track = Track::create(['name' => $trackName]);
                // Attach the track to the DJ with the default price
                $dj->tracks()->attach($track->id, ['price' => $dj->price]);
        
                $validated['track_id'] = $track->id;
            }
        }
       
        $track = Track::find($validated['track_id']);
        
        $user_id = Auth::id();
        if (!$user_id && $request->telegram_id) {
            $userController = new UserController();
            $userequest = new Request(['telegram_id' => $request->telegram_id]);
            $user = $userController->login($userequest);
    
            if ($user) {
                $user_id = $user->id;
            }
        }
        if (!$dj || !$track) {
            return response()->json(['error' => 'DJ or Track not found'], 404);
        }
    
    
        $order = Order::create([
            'user_id' => $user_id,
            'dj_id' => $validated['dj_id'],
            'track_id' => $validated['track_id'],
            'price' => $validated['price'],
            'message' => $validated['message'] ?? '',
            'timezone' => $validated['timezone'] ?? '',
            'status' => 'pending',
        ]);
    

        $telegram = $this->useTelegram();
        $userTelegramId = Auth::user()->telegram_id;
        $djTelegramId = $dj->telegram_id;

        $webAppDirectUrl = config('webapp.direct_url');
        $webAppDirectUrlDj = config('webapp.direct_url_dj'); 
        $tgWebAppUrl = "{$webAppDirectUrl}?startapp=order_{$order->id}";
        $tgWebAppUrlDj = "{$webAppDirectUrlDj}?startapp=order_{$order->id}";
        
        $message = "\nDJ: {$dj->stage_name}\nТрек: {$track->name}\nЦена: {$order->price}\nСообщение: {$order->message}";

        // User Inline Keyboard
        $userKeyboard = new InlineKeyboardMarkup([
            [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrl]],
            [['text' => '🙅‍♂️Отменить', 'callback_data' => "cancel_{$order->id}"]],
        ]);

        if ($userTelegramId) {
            $telegram->notifyUser($userTelegramId, "🎉 #заказ_{$order->id} отправлен:{$message}", null, false, null, $userKeyboard);
        }

       

        // DJ Inline Keyboard
        $djKeyboard = new InlineKeyboardMarkup([
            [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrlDj]],
            [['text' => '✅Принять', 'callback_data' => "accept_{$order->id}"]],
            [['text' => '💰Изменить Цену', 'callback_data' => "change_price_{$order->id}"]],
            [['text' => '💩Отказать в песне', 'callback_data' => "decline_{$order->id}"]],
        ]);

        if ($djTelegramId) {
            $telegram->notifyDj($djTelegramId, "🎧У вас новый #заказ_{$order->id}! {$message}", null, false, null, $djKeyboard);
        }

        return response()->json($order);
    }

    /**
     * @OA\Patch(
     *      path="/orders/{order_id}/accept",
     *      operationId="acceptOrder",
     *      tags={"Order"},
     *      summary="Accept an order",
     *      description="Allows a DJ to accept an order",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="order_id",
     *          description="Order ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="price", type="number", format="float", example=19.99),
     *              @OA\Property(property="message", type="string", example="Order accepted")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order accepted successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *             property="order",
     *             type="object",
     *             ref="#/components/schemas/Order"
     *         ),
     *         @OA\Property(
     *             property="transaction",
     *             type="object",
     *             ref="#/components/schemas/Transaction"
     *         ))
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function acceptOrder(Request $request, $order_id)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:255',
            'timezone' => 'nullable|string'
        ]);
    
        $order = Order::find($order_id);
    
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
    
        $priceChanged = $order->price != $validated['price'];
    
        if ($priceChanged) {
            $order->status = Order::STATUS_PRICE_CHANGED;
        } else {
            $order->status = Order::STATUS_ACCEPTED;
        }
    
        $order->price = $validated['price'];
        $order->message = $validated['message'] ?? 'Order accepted';
        $order->timezone = $validated['timezone'] ?? $order->timezone;
        $order->save();
    
        $yookassa = $this->yooKassaService;
        try {
            // Always create a new transaction, if there is pending transaction, it will be cancelled
            $transaction = $order->createTransaction($order->price, $yookassa);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        event(new OrderUpdated($order));

        $track = $order->track;
        $user = $order->user;
        $dj = $order->dj;
        $userTelegramId = $user->telegram_id;
        $telegram = $this->useTelegram();

        $message = "\nDJ: {$dj->stage_name}\nТрек: {$track->name}\nЦена: {$order->price}\nСообщение: {$order->message}";

        // User Inline Keyboard with payment link
        $userKeyboard = new InlineKeyboardMarkup([
            [['text' => '💳Оплатить', 'url' => $transaction->payment_url]],
            [['text' => '🙅‍♂️Отменить', 'callback_data' => "cancel_{$order->id}"]],
        ]);

        if ($userTelegramId) {
            $telegram->notifyUser($userTelegramId, "🎉 #заказ_{$order->id} принят:{$message}", null, false, null, $userKeyboard);
        }
        
    
        return response()->json(['order' => $order, 'transaction' => $transaction]);
    }

    public function updateTime(Request $request, $order_id)
    {
        $validated = $request->validate([
            'time_slot' => 'required|date_format:Y-m-d\TH:i' // Validate datetime-local format
        ]);
    
        $order = Order::find($order_id);
    
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
    
        // Convert time_slot to the correct format
        $datetime = new \DateTime($validated['time_slot']);
        $order->time_slot = $datetime->format('Y-m-d H:i:s'); // Save in server timezone
        $order->save();
    
        return response()->json(['order' => $order]);
    }

    /**
     * @OA\Patch(
     *      path="/orders/{order_id}/decline",
     *      operationId="declineOrder",
     *      tags={"Order"},
     *      summary="Decline an order",
     *      description="Allows a DJ to decline an order",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="order_id",
     *          description="Order ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Order declined")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order declined successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Order declined")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function declineOrder(Request $request, $order_id)
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:255',
        ]);

        $order = Order::find($order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->status = Order::STATUS_DECLINED;
        $order->message = $validated['message'] ?? 'Order declined';
        $order->save();

        return response()->json($order);
    }
    /**
     * @OA\Patch(
     *      path="/orders/{order_id}/cancel",
     *      operationId="cancelOrder",
     *      tags={"Order"},
     *      summary="Cancel an order",
     *      description="Allows a user to cancel an order and associated transactions",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="order_id",
     *          description="Order ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order and associated transactions cancelled successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Order and associated transactions cancelled")
     *          )
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function cancelOrder($order_id)
    {
        $order = Order::find($order_id);

        if (!$order) {
            Log::error("Order not found for cancellation", ['order_id' => $order_id]);
            return response()->json(['error' => 'Order not found'], 404);
        }
        $order->cancel();

        return response()->json(['success' => true, 'message' => 'Order and associated transactions cancelled'], 200);
    }


    /**
     * @OA\Get(
     *      path="/dj/{dj_id}/orders",
     *      operationId="getOrdersForDJ",
     *      tags={"Order"},
     *      summary="Get orders for DJ",
     *      description="Returns the list of orders for a specific DJ",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="dj_id",
     *          description="DJ ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Order")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="DJ not found")
     * )
     */
    public function getOrdersForDJ($dj_id)
    {
        $dj = DJ::find($dj_id);

        if (!$dj) {
            return response()->json(['error' => 'DJ not found'], 404);
        }

        $orders = Order::where('dj_id', $dj_id)->with(['track:id,name', 'dj:id,stage_name'])->orderBy('updated_at', 'desc')->get();

        return response()->json($orders);
    }

    /**
     * @OA\Get(
     *      path="/user/orders",
     *      operationId="getOrdersForUser",
     *      tags={"Order"},
     *      summary="Get orders for user",
     *      description="Returns the list of orders for the authenticated user",
     *      security={{"telegramAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Order")
     *      ),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getOrdersForUser()
    {
        $orders = Auth::user()->orders()
                ->with(['track:id,name', 'dj:id,stage_name'])
                ->orderBy('updated_at', 'desc')
                ->get();

        return response()->json($orders);
    }

    /**
     * @OA\Get(
     *      path="/orders/{id}",
     *      operationId="getOrderById",
     *      tags={"Order"},
     *      summary="Get order by ID",
     *      description="Returns the details of a specific order for the authenticated user",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Order ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Order")
     *      ),
     *      @OA\Response(response=404, description="Order not found"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getOrderById($id)
    {
        $order = Order::with(['track:id,name', 'dj:id,stage_name'])
                    ->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * @OA\Get(
     *      path="/orders/{id}/status",
     *      operationId="getOrderStatus",
     *      tags={"Order"},
     *      summary="Get order status by ID",
     *      description="Returns the status of a specific order for the authenticated user",
     *      security={{"telegramAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Order ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Order not found"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getOrderStatus($id)
    {
        $order = Order::select('status')->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json(['status' => $order->status]);
    }


    public function streamUpdates($order_id)
    {
        return response()->stream(function () use ($order_id) {
            // Fetch the order by ID
            $order = Order::with(['track:id,name', 'dj:id,stage_name'])->find($order_id);
    
            if (!$order) {
                // Send an error message if the order is not found
                echo "data: " . json_encode(['error' => 'Order not found']) . "\n\n";
                flush();
                return;
            }
    
            // Send the order data as a JSON response
            echo "data: " . json_encode($order) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}