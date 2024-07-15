<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $bot = new Client(config('telegram.bot_token'));

        // Handle commands and messages
        $bot->command('ping', function ($message) use ($bot) {
            $bot->sendMessage($message->getChat()->getId(), 'pong!');
        });

        $bot->on(function ($update) use ($bot) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            // Check if there's an ongoing session for this chat ID
            $session = Cache::get($chatId);

            if ($session && $session['action'] == 'collect_price') {
                $price = floatval($text);
                Cache::put($chatId, ['action' => 'collect_message', 'order_id' => $session['order_id'], 'price' => $price], now()->addMinutes(5));
                $bot->sendMessage($chatId, 'Please provide a message for the order:');
                return;
            }

            if ($session && $session['action'] == 'collect_message') {
                $orderId = $session['order_id'];
                $price = $session['price'];
                $this->acceptOrder($chatId, $orderId, $price, $text);
                Cache::forget($chatId);
                $bot->sendMessage($chatId, 'Order accepted successfully.');
                return;
            }

            if ($session && $session['action'] == 'collect_decline_message') {
                $orderId = $session['order_id'];
                $this->declineOrder($chatId, $orderId, $text);
                Cache::forget($chatId);
                $bot->sendMessage($chatId, 'Order declined successfully.');
                return;
            }

            // Process the callback data
            if (strpos($text, 'accept_') !== false) {
                $orderId = str_replace('accept_', '', $text);
                Cache::put($chatId, ['action' => 'collect_price', 'order_id' => $orderId], now()->addMinutes(5));
                $bot->sendMessage($chatId, 'Please provide the price for the order:');
            } elseif (strpos($text, 'decline_') !== false) {
                $orderId = str_replace('decline_', '', $text);
                Cache::put($chatId, ['action' => 'collect_decline_message', 'order_id' => $orderId], now()->addMinutes(5));
                $bot->sendMessage($chatId, 'Please provide a message for declining the order:');
            } elseif (strpos($text, 'cancel_') !== false) {
                $orderId = str_replace('cancel_', '', $text);
                $this->cancelOrder($orderId);
                $bot->sendMessage($chatId, 'Order canceled.');
            } elseif (strpos($text, 'change_price_') !== false) {
                $bot->sendMessage($chatId, 'Change price action not implemented.');
            }
        }, function () {
            return true;
        });

        try {
            $bot->run();
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function acceptOrder($chatId, $orderId, $price, $message)
    {
        $request = Request::create("/orders/{$orderId}/accept", 'PATCH', [
            'price' => $price,
            'message' => $message,
        ]);
        return app()->handle($request);
    }

    protected function declineOrder($chatId, $orderId, $message)
    {
        $request = Request::create("/orders/{$orderId}/decline", 'PATCH', [
            'message' => $message,
        ]);
        return app()->handle($request);
    }

    protected function cancelOrder($orderId)
    {
        $request = Request::create("/orders/{$orderId}/cancel", 'PATCH');
        return app()->handle($request);
    }
}