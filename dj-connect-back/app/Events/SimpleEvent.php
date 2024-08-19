<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimpleEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
        Log::info('SimpleEvent created with message: ' . $message);
    }

    public function broadcastOn()
    {
        Log::info('SimpleEvent broadcastOn method called.');
        return new Channel('simple-channel');
    }

    public function broadcastAs()
    {
        return 'simpleEvent';
    }
}