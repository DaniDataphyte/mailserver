<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\DispatchCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class DispatchScheduledCampaigns extends Command
{
    protected $signature   = 'campaigns:dispatch-scheduled';
    protected $description = 'Find all scheduled campaigns that are due and dispatch them';

    public function handle(): void
    {
        $due = Campaign::due()->get();

        if ($due->isEmpty()) {
            $this->line('No campaigns due.');
            return;
        }

        foreach ($due as $campaign) {
            DispatchCampaignJob::dispatch($campaign->id)
                ->onQueue('campaigns');

            $this->info("Dispatched campaign #{$campaign->id}: {$campaign->name}");
        }

        $this->info("Total dispatched: {$due->count()}");
    }
}
