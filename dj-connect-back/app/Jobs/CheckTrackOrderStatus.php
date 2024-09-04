<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Traits\UsesTelegram;
use Illuminate\Support\Facades\Log;

class CheckTrackOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesTelegram;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Retrieve all unpaid orders where the track has not been played
        $potentialOrders = Order::whereHas('transactions', function ($query) {
                $query->where('status', Transaction::STATUS_PAID);
            })
            ->where('track_played', false) // Only get orders where the track has not been played
            ->get();
    
        foreach ($potentialOrders as $order) {
            $timezone = $order->timezone ?? 'Europe/Moscow';
            $timeslot = $order->time_slot; 

            if(!$timeslot){
                return;
            }
            // Convert the time_slot to the specified timezone
            $orderTimeSlot = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $timeslot, $timezone);
    
            // Convert current time to the order's timezone
            $nowInOrderTimezone = now()->setTimezone($timezone);
            
            // If the reminder hasn't been sent and it's time to send it
            if (!$order->notification_sent && $nowInOrderTimezone->greaterThanOrEqualTo($orderTimeSlot->subMinutes(5))) {
                // Send notification to DJ and User
                $this->notifyClient($order);
                $this->sendReminderToDJ($order);

                $order->update(['notification_sent' => true]);
            }

            // 10 mins after track shoudl be played
            if (!$order->reminder_sent && $nowInOrderTimezone->greaterThanOrEqualTo($orderTimeSlot->addMinutes(10))) {
                // Check if the track has been manually marked as played
                if ($order->track_played) {
                    // Close the order and thank the client
                    $order->update(['status' => 'completed']);
                    $this->thankClient($order);
                } elseif (!$order->reminder_sent && !$order->track_played) {
                    // If the track wasn't played, remind the DJ again
                    $this->remindDJToPlayTrack($order);

                    $order->update(['reminder_sent' => true]);
                }
            }
        }
    }

    protected function sendReminderToDJ(Order $order)
    {
        // Инициализируем Telegram клиента
        $telegram = $this->useTelegram();

        // Получаем название трека
        $trackName = $order->track->name;

        $telegram_id = $order->dj->telegram_id;
        $webAppDirectUrlDj = config('webapp.direct_url_dj'); 
        $tgWebAppUrlDj = "{$webAppDirectUrlDj}?startapp=order_{$order->id}";

        // Клавиатура для DJ
        $djKeyboard = new InlineKeyboardMarkup([
            [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrlDj]],
            [['text' => '✅Поставил!', 'callback_data' => "finish_{$order->id}"]],
        ]);

        // Отправляем напоминание DJ
        if ($telegram_id) {
            $telegram->notifyDj($telegram_id, "🎧 Напоминание: нужно поставить трек \"{$trackName}\" для заказа #{$order->id} через 5 минут!", null, false, null, $djKeyboard);
        }

        Log::info("Напоминание отправлено DJ для заказа {$order->id}");
    }

    protected function notifyClient(Order $order)
    {
        // Инициализируем Telegram клиента
        $telegram = $this->useTelegram();

        // Получаем название трека
        $trackName = $order->track->name;

        $telegram_id = $order->user->telegram_id;

        $webAppDirectUrl = config('webapp.direct_url');
        $tgWebAppUrl = "{$webAppDirectUrl}?startapp=order_{$order->id}";
        // Клавиатура для пользователя
        $userKeyboard = new InlineKeyboardMarkup([
            [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrl]],
        ]);

        // Уведомляем клиента
        if ($telegram_id) {
            $telegram->notifyUser($telegram_id, "🎉 Ваш трек \"{$trackName}\" скоро будет сыгран! Заказ #{$order->id} в обработке.", null, false, null, $userKeyboard);
        }

        Log::info("Клиент уведомлен для заказа {$order->id}");
    }

    protected function thankClient(Order $order)
    {
        // Инициализируем Telegram клиента
        $telegram = $this->useTelegram();

        // Получаем название трека
        $trackName = $order->track->name;

        $telegram_id = $order->user->telegram_id;

        // Отправляем благодарность клиенту
        if ($telegram_id) {
            $telegram->notifyUser($telegram_id, "🙏 Спасибо за ваш заказ! Трек \"{$trackName}\" для заказа #{$order->id} был сыгран.", null, false, null);
        }

        Log::info("Благодарность отправлена клиенту для заказа {$order->id}");
    }

    protected function remindDJToPlayTrack(Order $order)
    {
        // Инициализируем Telegram клиента
        $telegram = $this->useTelegram();

        // Получаем название трека
        $trackName = $order->track->name;

        $telegram_id = $order->dj->telegram_id;

        $webAppDirectUrlDj = config('webapp.direct_url_dj'); 
        $tgWebAppUrlDj = "{$webAppDirectUrlDj}?startapp=order_{$order->id}";
        // Клавиатура для DJ
        $djKeyboard = new InlineKeyboardMarkup([
            [['text' => '❇️Открыть заказ', 'url' => $tgWebAppUrlDj]],
            [['text' => '✅Поставил!', 'callback_data' => "finish_{$order->id}"]],
        ]);

        // Напоминаем DJ поставить трек
        if ($telegram_id) {
            $telegram->notifyDj($telegram_id, "⚠️ Напоминание: поставьте трек \"{$trackName}\" для заказа #{$order->id} в течение 10 минут!", null, false, null, $djKeyboard);
        }

        Log::info("Напоминание поставить трек отправлено DJ для заказа {$order->id}");
    }
}