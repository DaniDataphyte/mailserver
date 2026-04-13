<?php

namespace App\Jobs\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Services\Newsletter\AudienceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the audience for a campaign, creates CampaignSend rows in bulk,
 * then fans out a SendNewsletterEmailJob for each subscriber.
 *
 * Runs on the `campaigns` queue.
 */
class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;   // dispatch is idempotent; don't retry
    public int $timeout = 600; // 10 min — large lists can take time

    public function __construct(public readonly int $campaignId) {}

    /* ------------------------------------------------------------------ */

    public function handle(AudienceResolver $resolver): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("DispatchCampaignJob: campaign {$this->campaignId} not found");
            return;
        }

        // Guard: only process campaigns not yet dispatched (total_recipients=0 allows
        // re-entry when the controller has already set status to 'sending').
        if (! in_array($campaign->status, ['draft', 'scheduled', 'sending'])) {
            Log::info("DispatchCampaignJob: campaign {$campaign->id} status={$campaign->status} — skipping");
            return;
        }

        // If already sending with recipients, a prior job handled it — bail out.
        if ($campaign->status === 'sending' && $campaign->total_recipients > 0) {
            Log::info("DispatchCampaignJob: campaign {$campaign->id} already dispatched ({$campaign->total_recipients} recipients) — skipping");
            return;
        }

        // Mark as sending immediately to prevent double dispatch
        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        try {
            $subscribers = $resolver->resolve($campaign->load('audiences'));

            if ($subscribers->isEmpty()) {
                Log::warning("DispatchCampaignJob: campaign {$campaign->id} resolved 0 subscribers");
                $campaign->update(['status' => 'sent', 'total_recipients' => 0]);
                return;
            }

            $now        = now();
            $insertRows = [];

            foreach ($subscribers as $subscriber) {
                $insertRows[] = [
                    'campaign_id'   => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'status'        => 'queued',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            // Bulk insert (ignore duplicates — safe to re-run)
            DB::transaction(function () use ($campaign, $insertRows, $subscribers) {
                foreach (array_chunk($insertRows, 500) as $chunk) {
                    CampaignSend::insertOrIgnore($chunk);
                }

                $campaign->update(['total_recipients' => count($insertRows)]);
            });

            // Fan out individual send jobs
            $campaignSends = CampaignSend::where('campaign_id', $campaign->id)
                ->where('status', 'queued')
                ->get(['id', 'subscriber_id']);

            foreach ($campaignSends as $send) {
                SendNewsletterEmailJob::dispatch($send->id)
                    ->onQueue('emails');
            }

            Log::info("DispatchCampaignJob: campaign {$campaign->id} dispatched {$campaignSends->count()} sends");

        } catch (\Throwable $e) {
            $campaign->update(['status' => 'failed']);
            Log::error("DispatchCampaignJob: campaign {$campaign->id} failed — {$e->getMessage()}");
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    public function queue(): string
    {
        return 'campaigns';
    }
}
