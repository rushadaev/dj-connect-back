<?php

namespace App\Listeners;

use App\Events\SimpleEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogSimpleEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SimpleEvent $event): void
    {
        Log::info('SimpleEvent handled: ' . $event->message); 
    }
}
