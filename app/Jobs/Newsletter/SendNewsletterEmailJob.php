<?php

namespace App\Jobs\Newsletter;

use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sends a single newsletter email to one subscriber.
 *
 * Rate-limited to 15 concurrent sends at a time (Elastic Email fair-use limit).
 * Runs on the `emails` queue.
 */
class SendNewsletterEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly int $campaignSendId) {}

    /* ------------------------------------------------------------------ */

    public function middleware(): array
    {
        return [
            new RateLimited('newsletter-emails'),
        ];
    }

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $send = CampaignSend::with(['campaign', 'subscriber'])->find($this->campaignSendId);

        if (! $send) {
            Log::warning("SendNewsletterEmailJob: CampaignSend {$this->campaignSendId} not found");
            return;
        }

        // Skip if already processed (idempotency)
        if (! in_array($send->status, ['queued', 'failed'])) {
            return;
        }

        $campaign   = $send->campaign;
        $subscriber = $send->subscriber;

        if (! $campaign || ! $subscriber) {
            $send->update(['status' => 'failed', 'bounce_reason' => 'Missing campaign or subscriber']);
            return;
        }

        try {
            $mailable = new NewsletterMailable($campaign, $subscriber, (string) $send->id);

            Mail::to($subscriber->email, $subscriber->full_name)
                ->send($mailable);

            // Extract Elastic Email transaction ID from the sent message headers
            $transactionId = $this->extractTransactionId($mailable);

            $send->update([
                'status'                          => 'sent',
                'sent_at'                         => now(),
                'elastic_email_transaction_id'    => $transactionId,
            ]);

        } catch (\Throwable $e) {
            Log::error("SendNewsletterEmailJob: send {$send->id} failed — {$e->getMessage()}");

            $send->update([
                'status'       => 'failed',
                'failed_at'    => now(),
                'bounce_reason' => substr($e->getMessage(), 0, 255),
            ]);

            // Re-throw so the queue worker can retry
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    public function backoff(): array
    {
        return [60, 120, 300]; // 1 min, 2 min, 5 min
    }

    public function failed(\Throwable $exception): void
    {
        $send = CampaignSend::find($this->campaignSendId);
        if ($send && $send->status !== 'bounced') {
            $send->update([
                'status'       => 'failed',
                'failed_at'    => now(),
                'bounce_reason' => substr($exception->getMessage(), 0, 255),
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * After Mail::send() the Mailable has a ->mailer property with a SentMessage.
     * The ElasticEmailTransport stores the transaction ID in a header.
     */
    private function extractTransactionId(NewsletterMailable $mailable): ?string
    {
        try {
            // The sent message is accessible via the mailable's internal state
            // after send(); the transport stores it in 'X-ElasticEmail-TransactionId'
            $reflection = new \ReflectionProperty($mailable, 'sentMessage');
            $reflection->setAccessible(true);
            $sentMessage = $reflection->getValue($mailable);

            if ($sentMessage) {
                return $sentMessage->getOriginalMessage()
                    ->getHeaders()
                    ->get('X-ElasticEmail-TransactionId')
                    ?->getBodyAsString();
            }
        } catch (\Throwable) {
            // Non-critical — tracking still works via campaign_send ID
        }

        return null;
    }

    /* ------------------------------------------------------------------ */

    public function queue(): string
    {
        return 'emails';
    }
}
