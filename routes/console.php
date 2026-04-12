<?php

use App\Console\Commands\Newsletter\DispatchScheduledCampaigns;
use App\Console\Commands\Newsletter\SyncCampaignStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Newsletter Campaign Scheduler
|--------------------------------------------------------------------------
| Checks every 5 minutes for campaigns whose scheduled_at has passed
| and dispatches DispatchCampaignJob for each.
*/

Schedule::command(DispatchScheduledCampaigns::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/campaign-dispatch.log'));

// Reconcile missed webhook events every hour
Schedule::command(SyncCampaignStats::class, ['--hours=2'])
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/campaign-sync.log'));
