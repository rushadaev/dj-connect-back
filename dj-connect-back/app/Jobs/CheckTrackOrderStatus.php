<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckTrackOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $paidOrders = Order::where('dj_id', $dj_id)
            ->whereHas('transactions', function ($query) {
                $query->where('status', Transaction::STATUS_PAID);
            })
            ->where('time_slot', '>=', now()->addMinutes(-10))
            ->where('time_slot', '<=', now())
            ->get();
        

        foreach ($orders as $order) {
            // If the reminder hasn't been sent and it's time to send it
            if (!$order->reminder_sent && now()->greaterThanOrEqualTo($order->time_slot->subMinutes(5))) {
                // Send reminder to DJ
                $this->sendReminderToDJ($order);
                $order->update(['reminder_sent' => true]);
            }

            // If the DJ has indicated the track will play and the client notification hasn't been sent
            if ($order->reminder_sent && !$order->notification_sent && now()->greaterThanOrEqualTo($order->time_slot)) {
                // Notify the client
                $this->notifyClient($order);
                $order->update(['notification_sent' => true]);
            }

            // Check if the track has been manually marked as played
            if ($order->notification_sent && $order->track_played) {
                // Close the order and thank the client
                $order->update(['status' => 'completed']);
                $this->thankClient($order);
            } else if ($order->notification_sent && !$order->track_played) {
                // If the track wasn't played, remind the DJ again
                $this->remindDJToPlayTrack($order);
            }
        }
    }

    protected function sendReminderToDJ(Order $order)
    {
        // Logic to send reminder to DJ
        Log::info("Reminder sent to DJ for order {$order->id}");
    }

    protected function notifyClient(Order $order)
    {
        // Logic to notify client
        Log::info("Client notified for order {$order->id}");
    }

    protected function thankClient(Order $order)
    {
        // Logic to thank the client
        Log::info("Thank you message sent to client for order {$order->id}");
    }

    protected function remindDJToPlayTrack(Order $order)
    {
        // Logic to remind DJ to play the track
        Log::info("Reminder to play track sent to DJ for order {$order->id}");
    }
}