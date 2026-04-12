<?php

namespace Tests\Feature;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    private string $endpoint = '/webhooks/elastic-email';

    private array $deliveryPayload = [
        'EventType'     => 'Delivery',
        'TransactionID' => 'tx-abc-123',
        'To'            => 'reader@example.com',
        'Date'          => '2026-04-11T10:00:00+00:00',
    ];

    /* ------------------------------------------------------------------ */
    /* Secret Verification                                                  */
    /* ------------------------------------------------------------------ */

    public function test_rejects_request_with_wrong_secret(): void
    {
        $response = $this->postJson(
            $this->endpoint . '?secret=wrong-secret',
            $this->deliveryPayload
        );

        $response->assertStatus(401);
        $this->assertDatabaseCount('webhook_logs', 0);
    }

    public function test_accepts_request_with_correct_secret_in_query(): void
    {
        Queue::fake();

        $response = $this->postJson(
            $this->endpoint . '?secret=test-secret',
            $this->deliveryPayload
        );

        $response->assertStatus(200);
    }

    public function test_accepts_request_with_correct_secret_in_header(): void
    {
        Queue::fake();

        $response = $this->postJson(
            $this->endpoint,
            $this->deliveryPayload,
            ['X-Webhook-Secret' => 'test-secret']
        );

        $response->assertStatus(200);
    }

    /* ------------------------------------------------------------------ */
    /* JSON Payload                                                         */
    /* ------------------------------------------------------------------ */

    public function test_stores_webhook_log_from_json_payload(): void
    {
        Queue::fake();

        $this->postJson(
            $this->endpoint . '?secret=test-secret',
            $this->deliveryPayload
        );

        $this->assertDatabaseHas('webhook_logs', [
            'event_type'     => 'Delivery',
            'transaction_id' => 'tx-abc-123',
            'to_email'       => 'reader@example.com',
        ]);
    }

    public function test_dispatches_process_webhook_job_after_logging(): void
    {
        Queue::fake();

        $this->postJson(
            $this->endpoint . '?secret=test-secret',
            $this->deliveryPayload
        );

        Queue::assertPushedOn('webhooks', ProcessWebhookJob::class);
    }

    /* ------------------------------------------------------------------ */
    /* Form-encoded Payload                                                 */
    /* ------------------------------------------------------------------ */

    public function test_stores_webhook_log_from_form_encoded_payload(): void
    {
        Queue::fake();

        $this->post(
            $this->endpoint . '?secret=test-secret',
            [
                'eventtype'     => 'Open',
                'transactionid' => 'tx-open-456',
                'to'            => 'subscriber@example.com',
            ]
        );

        $this->assertDatabaseHas('webhook_logs', [
            'event_type'     => 'Open',
            'transaction_id' => 'tx-open-456',
            'to_email'       => 'subscriber@example.com',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Edge Cases                                                           */
    /* ------------------------------------------------------------------ */

    public function test_returns_400_for_empty_json_payload(): void
    {
        // Send an explicit empty JSON object — the controller rejects payloads with no keys
        $response = $this->call(
            'POST',
            $this->endpoint . '?secret=test-secret',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        $response->assertStatus(400);
        $this->assertDatabaseCount('webhook_logs', 0);
    }

    public function test_allows_unknown_event_types_through(): void
    {
        Queue::fake();

        $response = $this->postJson(
            $this->endpoint . '?secret=test-secret',
            [
                'EventType'     => 'SomeNewEvent',
                'TransactionID' => 'tx-999',
                'To'            => 'user@example.com',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('webhook_logs', ['event_type' => 'SomeNewEvent']);
    }
}
