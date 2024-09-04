<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\CheckTrackOrderStatus;
use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();




Artisan::command('checkOrders', function () {
    // Dispatch the job
    dispatch(new CheckTrackOrderStatus());
})->purpose('Check and process track orders')->everyFiveSeconds();