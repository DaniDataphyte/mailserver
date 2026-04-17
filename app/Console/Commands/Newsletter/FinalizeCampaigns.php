<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Console\Command;

/**
 * Transitions any 'sending' campaign to 'sent' once all its sends
 * have left the 'queued' state. Runs every 5 minutes via scheduler.
 */
class FinalizeCampaigns extends Command
{
    protected $signature   = 'campaigns:finalize';
    protected $description = 'Mark campaigns as sent once all sends are dispatched';

    public function handle(): void
    {
        $sending = Campaign::where('status', 'sending')->get();

        if ($sending->isEmpty()) {
            return;
        }

        foreach ($sending as $campaign) {
            $stillQueued = CampaignSend::where('campaign_id', $campaign->id)
                ->where('status', 'queued')
                ->exists();

            if (! $stillQueued) {
                $campaign->update(['status' => 'sent']);
                $this->line("Campaign #{$campaign->id} \"{$campaign->name}\" → sent");
                \Illuminate\Support\Facades\Log::info("FinalizeCampaigns: campaign {$campaign->id} marked sent");
            }
        }
    }
}
