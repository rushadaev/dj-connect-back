<?php

namespace App\Services;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new BotApi(config('telegram.bot_token'));
    }

    public function sendMessage($chatId, $message, $parseMode = null, $disableWebPagePreview = false, $replyToMessageId = null, InlineKeyboardMarkup $replyMarkup = null)
    {
        return $this->telegram->sendMessage($chatId, $message, $parseMode, $disableWebPagePreview, $replyToMessageId, $replyMarkup);
    }

    public function sendPhoto($chatId, $photo, $caption = null, $parseMode = null, $disableNotification = false, $replyToMessageId = null, InlineKeyboardMarkup $replyMarkup = null)
    {
        try {
            $response = $this->telegram->sendPhoto($chatId, $photo, $caption);

            if ($response === false) {
                // Log error or throw an exception
                \Log::error("Failed to send photo via Telegram", ['chatId' => $chatId]);
                throw new \Exception("Failed to send photo to Telegram.");
            }

            return $response;
        } catch (\Exception $e) {
            \Log::error("Error sending photo to Telegram: " . $e->getMessage(), ['chatId' => $chatId]);
            return false; // or handle the exception in another way
        }
    }
}