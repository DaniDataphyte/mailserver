<?php

namespace Tests\Feature;

use App\Models\WebhookLog;
use Tests\TestCase;

class WebhookLogModelTest extends TestCase
{
    public function test_unprocessed_scope_returns_only_unprocessed_logs(): void
    {
        WebhookLog::factory()->count(3)->create();                 // unprocessed
        WebhookLog::factory()->processed()->create();              // processed
        WebhookLog::factory()->failed()->create();                 // has error

        $this->assertCount(3, WebhookLog::unprocessed()->get());
    }

    public function test_failed_scope_returns_only_logs_with_errors(): void
    {
        WebhookLog::factory()->count(2)->create();
        WebhookLog::factory()->failed()->count(2)->create();

        $this->assertCount(2, WebhookLog::failed()->get());
    }

    public function test_mark_processed_sets_timestamp_and_clears_error(): void
    {
        $log = WebhookLog::factory()->failed()->create();
        $this->assertNotNull($log->error);

        $log->markProcessed();
        $log->refresh();

        $this->assertNotNull($log->processed_at);
        $this->assertNull($log->error);
    }

    public function test_mark_failed_sets_error_message(): void
    {
        $log = WebhookLog::factory()->create();
        $this->assertNull($log->error);

        $log->markFailed('Subscriber not found');
        $log->refresh();

        $this->assertEquals('Subscriber not found', $log->error);
    }

    public function test_payload_cast_as_array(): void
    {
        $log = WebhookLog::factory()->create([
            'payload' => ['EventType' => 'Delivery', 'To' => 'user@example.com'],
        ]);

        $this->assertIsArray($log->fresh()->payload);
        $this->assertEquals('Delivery', $log->fresh()->payload['EventType']);
    }
}
