<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\UsesTelegram;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable;
    use UsesTelegram;
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $telegramId,
        public string $message,
        public string $parse_mode,
        public $keyboard = null,
        public $botToken = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $botToken = $this->botToken ?? config('telegram.bot_notification');
        $telegram = $this->useTelegram();
        $telegram->setBotToken($botToken);
    
        try {
            $telegram->sendMessage($this->telegramId, $this->message, $this->parse_mode, true, null, $this->keyboard);
        } catch (\TelegramBot\Api\HttpException $e) {
            if ($e->getCode() === 403 && strpos($e->getMessage(), 'bot was blocked by the user') !== false) {
                
                \Log::error("Telegram Bot was blocked by the user: {$this->telegramId}");
            } else {
                // Log any other TelegramBot API exception
                \Log::error("Telegram API Error: {$e->getMessage()}", ['exception' => $e]);
            }
        } catch (\Exception $e) {
            // Log any other general exception
            \Log::error("An error occurred: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}