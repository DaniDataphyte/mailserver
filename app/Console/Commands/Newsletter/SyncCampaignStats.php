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
                              {--hours=48  : Look back N hours for unconfirmed sends}
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

        // Include 'delivered' and 'opened' so they can be upgraded to
        // 'opened' or 'clicked' respectively when the API returns new data.
        $query = CampaignSend::query()
            ->whereIn('status', ['sent', 'pending', 'delivered', 'opened'])
            ->whereNotNull('elastic_email_transaction_id')
            ->where('sent_at', '>=', now()->subHours($hours))
            ->with(['campaign', 'subscriber']);

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
                $result = $api->emailsByTransactionidStatusGet(
                    $send->elastic_email_transaction_id,
                    show_failed: true,
                    show_sent: true,
                    show_delivered: true,
                    show_pending: true,
                    show_opened: true,
                    show_clicked: true,
                    show_abuse: true,
                    show_unsubscribed: true,
                    show_errors: true,
                    show_message_ids: true,
                );

                if (! $result) {
                    continue;
                }

                $status = $this->normaliseStatusFromJob($result);

                if (! $status) {
                    continue;
                }

                // Never downgrade: skip if API returns a lower-priority status
                // than what is already recorded (e.g. don't overwrite 'clicked' with 'delivered')
                $priority = ['failed' => 0, 'delivered' => 1, 'opened' => 2, 'clicked' => 3];
                $current  = $priority[$send->status] ?? -1;
                $incoming = $priority[$status]        ?? -1;
                if ($incoming <= $current) {
                    continue;
                }

                $eventDate = now()->toIso8601String();
                $bounceReason = $this->extractFailureReason($result);

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
                        'BounceError'   => $bounceReason,
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

    private function normaliseStatusFromJob(object $result): ?string
    {
        return match (true) {
            (int) ($result->getClickedCount() ?? 0) > 0      => 'clicked',
            (int) ($result->getOpenedCount() ?? 0) > 0       => 'opened',
            (int) ($result->getDeliveredCount() ?? 0) > 0    => 'delivered',
            (int) ($result->getAbuseReportsCount() ?? 0) > 0 => 'abusereport',
            (int) ($result->getUnsubscribedCount() ?? 0) > 0 => 'unsubscribed',
            (int) ($result->getFailedCount() ?? 0) > 0       => 'failed',
            default                                          => null,
        };
    }

    private function extractFailureReason(object $result): string
    {
        $failed = $result->getFailed() ?? [];

        if (empty($failed)) {
            return '';
        }

        $firstFailure = $failed[0];

        return method_exists($firstFailure, 'getError')
            ? (string) ($firstFailure->getError() ?? '')
            : '';
    }
}
