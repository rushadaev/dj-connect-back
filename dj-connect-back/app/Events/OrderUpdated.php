<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $order
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcasting on a channel specific to the order ID
        return new Channel('order-update-' . $this->order->id);
    }

    /**
     * Get the event name that should be broadcasted.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'orderUpdated';
    }
}
