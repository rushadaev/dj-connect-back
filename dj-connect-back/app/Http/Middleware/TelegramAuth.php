<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TelegramAuth
{
    public function handle(Request $request, Closure $next)
    {
        $initData = $request->header('Telegram-Init-Data');

        if (!$initData) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $telegramBotToken = config('telegram.bot_token');
        $data = $this->parseInitData($initData);
        if (!$this->validateTelegramAuth($data)) {
            Log::debug('Validation failed', [
                'key' => $telegramBotToken,
                'init_data' => $initData,
                'parsed_data' => $data,
                'calculated_hash' => $this->calculateHash($data),
                'received_hash' => $data['hash'],
            ]);
            return response()->json(['error' => 'Invalid Telegram data'], 403);
        }

        $userData = json_decode($data['user'], true);
        $user = User::firstOrCreate(
            ['telegram_id' => $userData['id']],
            [
                'telegram_id' => $userData['id'],
                'name' => $userData['first_name'],
                'phone_number' => $userData['username'] ?? null,
                'email' => $userData['username'] . '@telegram.com',
            ]
        );

        // Authenticate the user
        Auth::login($user);

        // You can set the user on the request if needed, though this is optional
        $request->user = $user;

        return $next($request);
    }

    private function validateTelegramAuth($data)
    {
        $calculatedHash = $this->calculateHash($data);
        return hash_equals($calculatedHash, $data['hash']);
    }

    private function calculateHash($data)
    {
        $checkString = collect($data)->except('hash')->sortKeys()->map(function($value, $key) {
            return "{$key}={$value}";
        })->implode("\n");

        $telegramBotToken = config('telegram.bot_token');
        $secretKey = hash_hmac('sha256', $telegramBotToken, 'WebAppData', true);
        return hash_hmac('sha256', $checkString, $secretKey);
    }

    private function parseInitData($initData)
    {
        $pairs = explode('&', $initData);
        $data = [];
        foreach ($pairs as $pair) {
            list($key, $value) = explode('=', $pair, 2);  // Ensure we handle '=' in values
            $data[$key] = urldecode($value);
        }
        return $data;
    }
}