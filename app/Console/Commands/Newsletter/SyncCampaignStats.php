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
 * Designed to run as two scheduled jobs:
 *
 *  JOB 1 — Recent (hourly, low cost):
 *    Checks sends from the last SYNC_RECENT_HOURS hours.
 *    Catches deliveries, opens, and clicks shortly after sending.
 *    Limit: SYNC_RECENT_LIMIT sends per run.
 *
 *  JOB 2 — Deep scan (daily at 2 AM, off-peak):
 *    Checks all unresolved sends from the last SYNC_DEEP_DAYS days.
 *    Catches late opens/clicks from subscribers who engage days later.
 *    Limit: SYNC_DEEP_LIMIT sends per run.
 *
 * Usage:
 *   php artisan campaigns:sync-stats                        ← all unresolved, no limit
 *   php artisan campaigns:sync-stats --hours=8 --limit=500  ← recent job
 *   php artisan campaigns:sync-stats --days=30 --limit=2000 ← deep scan job
 *   php artisan campaigns:sync-stats --campaign=123 --dry-run
 */
class SyncCampaignStats extends Command
{
    protected $signature = 'campaigns:sync-stats
                              {--campaign= : Sync a specific campaign ID only}
                              {--hours=    : Look back N hours (overrides --days)}
                              {--days=     : Look back N days (default: 30)}
                              {--limit=    : Max sends to process per run (0 = unlimited)}
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

        $hours      = $this->option('hours') ? (int) $this->option('hours') : null;
        $days       = $this->option('days')  ? (int) $this->option('days')  : 30;
        $limit      = $this->option('limit') !== null ? (int) $this->option('limit') : 0;
        $campaignId = $this->option('campaign');
        $dryRun     = $this->option('dry-run');

        $window = $hours ? "{$hours}h" : "{$days}d";
        $cap    = $limit > 0 ? " (cap: {$limit})" : '';
        $this->info("Syncing campaign stats (window: {$window}{$cap})" . ($dryRun ? ' [dry-run]' : '') . '...');

        $api = $this->buildApi($apiKey);

        // Only check sends that have not yet reached a terminal/fully-resolved status.
        // 'clicked'   = highest trackable status — nothing further to do.
        // 'bounced' / 'failed' / 'complained' = terminal — skip permanently.
        // Oldest-first ensures the deep scan clears the longest-waiting sends first.
        $cutoff = $hours ? now()->subHours($hours) : now()->subDays($days);

        $query = CampaignSend::query()
            ->whereIn('status', ['sent', 'pending', 'delivered', 'opened'])
            ->whereNotNull('elastic_email_transaction_id')
            ->where('sent_at', '>=', $cutoff)
            ->orderBy('sent_at', 'asc')
            ->with(['campaign', 'subscriber']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $sends = $query->get();

        if ($sends->isEmpty()) {
            $this->line('No unresolved sends found.');
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

        $this->info("Queued {$synced} / {$sends->count()} sync webhook jobs.");
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
