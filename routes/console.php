<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\PayoutCommissionsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::job(new PayoutCommissionsJob)
//     ->weeklyOn(4, '08:00');

Artisan::command('payout:commissions', function () {
    PayoutCommissionsJob::dispatch();
    $this->info('Commission payout job dispatched!');
})->purpose('Dispatch commission payout job');
