<?php

namespace Tests\Feature;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use App\Models\WebhookLog;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
{
    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    private function makeLog(string $event, string $txId, string $email, array $extra = []): WebhookLog
    {
        return WebhookLog::factory()->forEvent($event, $txId, $email)->create(array_merge([
            'payload' => array_merge([
                'EventType'     => $event,
                'TransactionID' => $txId,
                'To'            => $email,
                'Date'          => now()->toIso8601String(),
            ], $extra),
        ], $extra));
    }

    private function makeSend(string $txId, array $subscriberState = []): CampaignSend
    {
        $subscriber = Subscriber::factory()->create($subscriberState);

        return CampaignSend::factory()->create([
            'subscriber_id'                => $subscriber->id,
            'elastic_email_transaction_id' => $txId,
            'status'                       => 'sent',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Delivery                                                             */
    /* ------------------------------------------------------------------ */

    public function test_delivery_event_marks_send_delivered(): void
    {
        $send = $this->makeSend('tx-del-001');
        $log  = $this->makeLog('Delivery', 'tx-del-001', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('delivered', $send->fresh()->status);
        $this->assertNotNull($send->fresh()->delivered_at);
    }

    public function test_delivery_does_not_downgrade_opened_status(): void
    {
        $send = $this->makeSend('tx-del-002');
        $send->update(['status' => 'opened', 'opened_at' => now()->subMinutes(5)]);

        $log = $this->makeLog('Delivery', 'tx-del-002', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('opened', $send->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* Open                                                                 */
    /* ------------------------------------------------------------------ */

    public function test_open_event_marks_send_opened(): void
    {
        $send = $this->makeSend('tx-open-001');
        $log  = $this->makeLog('Open', 'tx-open-001', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('opened', $send->fresh()->status);
        $this->assertNotNull($send->fresh()->opened_at);
    }

    public function test_open_event_also_sets_delivered_at_if_missing(): void
    {
        $send = $this->makeSend('tx-open-002');
        $this->assertNull($send->delivered_at);

        $log = $this->makeLog('Open', 'tx-open-002', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertNotNull($send->fresh()->delivered_at);
    }

    /* ------------------------------------------------------------------ */
    /* Click                                                                */
    /* ------------------------------------------------------------------ */

    public function test_click_event_marks_send_clicked_and_records_link(): void
    {
        $send = $this->makeSend('tx-click-001');

        $log = WebhookLog::factory()->forEvent('Click', 'tx-click-001', $send->subscriber->email)->create([
            'payload' => [
                'EventType'     => 'Click',
                'TransactionID' => 'tx-click-001',
                'To'            => $send->subscriber->email,
                'Date'          => now()->toIso8601String(),
                'Link'          => 'https://dataphyte.com/story?utm_source=newsletter',
            ],
        ]);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('clicked', $send->fresh()->status);
        $this->assertNotNull($send->fresh()->clicked_at);
        $this->assertDatabaseHas('campaign_link_clicks', [
            'campaign_send_id' => $send->id,
            'url'              => 'https://dataphyte.com/story?utm_source=newsletter',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Hard Bounce                                                          */
    /* ------------------------------------------------------------------ */

    public function test_hard_bounce_marks_send_bounced(): void
    {
        $send = $this->makeSend('tx-bounce-001');
        $log  = $this->makeLog('Bounce', 'tx-bounce-001', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('bounced', $send->fresh()->status);
        $this->assertNotNull($send->fresh()->bounced_at);
    }

    public function test_hard_bounce_suppresses_active_subscriber(): void
    {
        $send       = $this->makeSend('tx-bounce-002', ['status' => 'active']);
        $subscriber = $send->subscriber;

        $log = $this->makeLog('Bounce', 'tx-bounce-002', $subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('bounced', $subscriber->fresh()->status);
    }

    public function test_hard_bounce_variant_bouncedhard_also_suppresses(): void
    {
        $send       = $this->makeSend('tx-bounce-003', ['status' => 'active']);
        $subscriber = $send->subscriber;

        $log = WebhookLog::factory()->create([
            'event_type'     => 'BouncedHard',
            'transaction_id' => 'tx-bounce-003',
            'to_email'       => $subscriber->email,
            'payload'        => ['EventType' => 'BouncedHard', 'TransactionID' => 'tx-bounce-003', 'To' => $subscriber->email, 'Date' => now()->toIso8601String()],
        ]);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('bounced', $subscriber->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* Unsubscribe / Complaint                                              */
    /* ------------------------------------------------------------------ */

    public function test_unsubscribe_event_suppresses_subscriber(): void
    {
        $send       = $this->makeSend('tx-unsub-001', ['status' => 'active']);
        $subscriber = $send->subscriber;

        $log = $this->makeLog('Unsubscribe', 'tx-unsub-001', $subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('unsubscribed', $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_complaint_event_suppresses_subscriber(): void
    {
        $send       = $this->makeSend('tx-complaint-001', ['status' => 'active']);
        $subscriber = $send->subscriber;

        $log = $this->makeLog('Complaint', 'tx-complaint-001', $subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('unsubscribed', $subscriber->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* Soft Bounce / Error                                                  */
    /* ------------------------------------------------------------------ */

    public function test_soft_bounce_marks_send_failed_without_suppressing_subscriber(): void
    {
        $send       = $this->makeSend('tx-soft-001', ['status' => 'active']);
        $subscriber = $send->subscriber;

        $log = WebhookLog::factory()->create([
            'event_type'     => 'BouncedSoft',
            'transaction_id' => 'tx-soft-001',
            'to_email'       => $subscriber->email,
            'payload'        => ['EventType' => 'BouncedSoft', 'TransactionID' => 'tx-soft-001', 'To' => $subscriber->email, 'Date' => now()->toIso8601String()],
        ]);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('failed', $send->fresh()->status);
        $this->assertEquals('active', $subscriber->fresh()->status); // not suppressed
    }

    /* ------------------------------------------------------------------ */
    /* Log Lifecycle                                                        */
    /* ------------------------------------------------------------------ */

    public function test_log_marked_processed_after_successful_handling(): void
    {
        $send = $this->makeSend('tx-proc-001');
        $log  = $this->makeLog('Delivery', 'tx-proc-001', $send->subscriber->email);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertNotNull($log->fresh()->processed_at);
        $this->assertNull($log->fresh()->error);
    }

    public function test_log_marked_processed_for_unknown_event(): void
    {
        // Unknown events skip processing but should still mark the log processed
        $log = WebhookLog::factory()->create([
            'event_type'     => 'UnknownXYZ',
            'transaction_id' => 'tx-unknown-001',
            'to_email'       => 'nobody@example.com',
            'payload'        => ['EventType' => 'UnknownXYZ'],
        ]);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertNotNull($log->fresh()->processed_at);
    }

    /* ------------------------------------------------------------------ */
    /* Fallback resolution by email                                        */
    /* ------------------------------------------------------------------ */

    public function test_resolves_send_by_email_when_no_transaction_id_match(): void
    {
        // Create a send with a different transaction ID but matching email
        $subscriber = Subscriber::factory()->create();
        $send = CampaignSend::factory()->create([
            'subscriber_id'                => $subscriber->id,
            'elastic_email_transaction_id' => 'different-tx',
            'status'                       => 'sent',
        ]);
        // Set campaign to 'sent' so fallback query includes it
        $send->campaign->update(['status' => 'sent']);

        $log = WebhookLog::factory()->create([
            'event_type'     => 'Delivery',
            'transaction_id' => 'no-match-tx',
            'to_email'       => $subscriber->email,
            'payload'        => [
                'EventType'     => 'Delivery',
                'TransactionID' => 'no-match-tx',
                'To'            => $subscriber->email,
                'Date'          => now()->toIso8601String(),
            ],
        ]);

        ProcessWebhookJob::dispatchSync($log->id);

        $this->assertEquals('delivered', $send->fresh()->status);
    }
}
