<?php

use App\Console\Commands\Newsletter\DispatchScheduledCampaigns;
use App\Console\Commands\Newsletter\FinalizeCampaigns;
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

// Finalize campaigns once all sends leave 'queued'
Schedule::command(FinalizeCampaigns::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/campaign-finalize.log'));

// ── Sync Job 1: Recent sends (hourly) ────────────────────────────────────
// Checks sends from the last SYNC_RECENT_HOURS hours.
// Lightweight — catches deliveries, opens, clicks shortly after sending.
// Limit controlled by SYNC_RECENT_LIMIT to prevent overloading the server.
Schedule::command(SyncCampaignStats::class, [
    '--hours=' . config('newsletter.sync_recent_hours', 8),
    '--limit=' . config('newsletter.sync_recent_limit', 500),
])
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/campaign-sync.log'));

// ── Sync Job 2: Deep scan (daily at 2 AM, off-peak) ──────────────────────
// Checks all unresolved sends from the last SYNC_DEEP_DAYS days.
// Catches late opens/clicks from subscribers who engage days later.
// Higher limit since it runs once a day during low-traffic hours.
Schedule::command(SyncCampaignStats::class, [
    '--days='  . config('newsletter.sync_deep_days',  30),
    '--limit=' . config('newsletter.sync_deep_limit', 2000),
])
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/campaign-sync-deep.log'));
