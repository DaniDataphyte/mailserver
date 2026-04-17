<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\SendNewsletterEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Console\Command;

/**
 * Processes queued campaign sends directly (bypasses Redis queue).
 * Rate limited to 300/min to stay within Elastic Email SMTP limits.
 */
class SendQueuedEmails extends Command
{
    protected $signature   = 'campaigns:send-queued {--campaign= : Limit to specific campaign ID}';
    protected $description = 'Send all queued campaign emails directly at 300/min';

    public function handle(): void
    {
        $query = CampaignSend::where('status', 'queued');

        if ($this->option('campaign')) {
            $query->where('campaign_id', $this->option('campaign'));
        }

        $total    = $query->count();
        $this->info("Processing {$total} queued sends at 300/min...");

        $sent     = 0;
        $failed   = 0;
        $batchStart = microtime(true);
        $batchCount = 0;

        $query->chunkById(50, function ($sends) use (&$sent, &$failed, &$batchStart, &$batchCount) {
            foreach ($sends as $send) {

                // Rate limit: 300/min = 5/sec
                // After every 300 sends, pause until 60s have elapsed
                if ($batchCount > 0 && $batchCount % 300 === 0) {
                    $elapsed = microtime(true) - $batchStart;
                    if ($elapsed < 60) {
                        $sleep = (int) ceil(60 - $elapsed);
                        $this->line("Rate limit pause: {$sleep}s...");
                        sleep($sleep);
                    }
                    $batchStart = microtime(true);
                }

                try {
                    $job = new SendNewsletterEmailJob($send->id);
                    $job->handle();
                    $sent++;
                } catch (\Throwable $e) {
                    $this->warn("Send #{$send->id} failed: " . $e->getMessage());
                    $failed++;
                }

                $batchCount++;

                if ($batchCount % 100 === 0) {
                    $this->line("Progress: {$batchCount} processed, {$sent} sent, {$failed} failed");
                }
            }
        });

        $this->info("Done. Sent: {$sent} | Failed: {$failed}");

        // Mark any campaign whose sends are all fully processed as 'sent'
        $this->finalizeCampaigns();
    }

    private function finalizeCampaigns(): void
    {
        $sending = Campaign::where('status', 'sending')->get();

        foreach ($sending as $campaign) {
            $queued = CampaignSend::where('campaign_id', $campaign->id)
                ->where('status', 'queued')
                ->exists();

            if (! $queued) {
                $campaign->update(['status' => 'sent']);
                $this->line("Campaign #{$campaign->id} marked as sent.");
            }
        }
    }
}
