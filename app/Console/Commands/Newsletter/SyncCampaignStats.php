<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\WebhookLog;
use ElasticEmail\Api\EmailsApi;
use ElasticEmail\Configuration;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fallback stats reconciliation command.
 *
 * Runs hourly to catch any events that were missed by webhooks
 * (network errors, Elastic Email retries that never arrived, etc.)
 *
 * Strategy:
 *  1. Find campaigns in 'sending' or 'sent' status with recent activity.
 *  2. For each campaign, find CampaignSends still in 'sent' status
 *     (i.e. sent but no delivery confirmation received yet).
 *  3. Query Elastic Email API v4 for message details by transaction ID.
 *  4. If status has changed, create a synthetic WebhookLog and dispatch
 *     ProcessWebhookJob to apply the same logic consistently.
 *
 * Usage:
 *   php artisan campaigns:sync-stats
 *   php artisan campaigns:sync-stats --campaign=123
 *   php artisan campaigns:sync-stats --hours=6
 */
class SyncCampaignStats extends Command
{
    protected $signature = 'campaigns:sync-stats
                              {--campaign= : Sync a specific campaign ID only}
                              {--hours=2   : Look back N hours for unconfirmed sends}
                              {--dry-run   : Report what would be synced without writing}';

    protected $description = 'Reconcile campaign delivery stats from Elastic Email API (fallback for missed webhooks)';

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $apiKey = config('mail.mailers.elasticemail.key');

        if (empty($apiKey)) {
            $this->error('ELASTIC_EMAIL_API_KEY is not set. Cannot sync stats.');
            return;
        }

        $hours      = (int) $this->option('hours');
        $campaignId = $this->option('campaign');
        $dryRun     = $this->option('dry-run');

        $this->info("Syncing campaign stats (lookback: {$hours}h)" . ($dryRun ? ' [dry-run]' : '') . '...');

        $api = $this->buildApi($apiKey);

        $query = CampaignSend::query()
            ->whereIn('status', ['sent', 'pending'])
            ->whereNotNull('elastic_email_transaction_id')
            ->where('sent_at', '>=', now()->subHours($hours))
            ->with('campaign');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $sends = $query->get();

        if ($sends->isEmpty()) {
            $this->line("No unconfirmed sends in the last {$hours}h.");
            return;
        }

        $this->info("Found {$sends->count()} sends to check.");
        $synced = 0;

        foreach ($sends as $send) {
            try {
                $result = $api->emailsByMsgidInfoGet($send->elastic_email_transaction_id);

                if (! $result) continue;

                $status    = strtolower($result->getStatus() ?? '');
                $eventDate = $result->getDateSent()?->format('Y-m-d\TH:i:s') ?? now()->toIso8601String();

                // Only synthesise a webhook if the API reports a terminal state
                if (! in_array($status, ['delivered', 'opened', 'clicked', 'bounced', 'failed', 'error', 'abusereport'])) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry-run] send #{$send->id} tx={$send->elastic_email_transaction_id} → {$status}");
                    $synced++;
                    continue;
                }

                // Create a synthetic WebhookLog and process it through the same pipeline
                $log = WebhookLog::create([
                    'event_type'     => $status,
                    'transaction_id' => $send->elastic_email_transaction_id,
                    'to_email'       => $send->subscriber?->email,
                    'payload'        => [
                        'EventType'     => $status,
                        'TransactionID' => $send->elastic_email_transaction_id,
                        'To'            => $send->subscriber?->email,
                        'Date'          => $eventDate,
                        'BounceError'   => $result->getStatusChangeReason() ?? '',
                        '_source'       => 'sync-command',
                    ],
                ]);

                ProcessWebhookJob::dispatch($log->id)->onQueue('webhooks');
                $synced++;

            } catch (\Throwable $e) {
                Log::warning("SyncCampaignStats: send #{$send->id} — {$e->getMessage()}");
                $this->warn("  send #{$send->id} error: {$e->getMessage()}");
            }
        }

        $this->info("Queued {$synced} sync webhook jobs.");
    }

    /* ------------------------------------------------------------------ */

    private function buildApi(string $apiKey): EmailsApi
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('X-ElasticEmail-ApiKey', $apiKey);

        return new EmailsApi(new Client(), $config);
    }
}
