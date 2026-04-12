<?php

namespace Tests\Unit;

use App\Models\Campaign;
use Tests\TestCase;

class CampaignSenderTest extends TestCase
{
    public function test_sender_uses_collection_config_for_insight(): void
    {
        $campaign = Campaign::factory()->insight()->make();

        $sender = $campaign->sender();

        $this->assertEquals('newsletter@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Dataphyte Insight',        $sender['from_name']);
    }

    public function test_sender_uses_collection_config_for_foundation(): void
    {
        $campaign = Campaign::factory()->foundation()->make();

        $sender = $campaign->sender();

        $this->assertEquals('newsletter@dataphyte.org', $sender['from_email']);
        $this->assertEquals('Dataphyte Foundation',     $sender['from_name']);
    }

    public function test_per_campaign_override_takes_precedence(): void
    {
        $campaign = Campaign::factory()->insight()->make([
            'from_email' => 'custom@dataphyte.com',
            'from_name'  => 'Custom Name',
        ]);

        $sender = $campaign->sender();

        $this->assertEquals('custom@dataphyte.com', $sender['from_email']);
        $this->assertEquals('Custom Name',          $sender['from_name']);
    }

    public function test_fallback_used_for_unknown_collection(): void
    {
        $campaign = Campaign::factory()->make([
            'collection' => 'unknown_collection',
            'from_email' => null,
            'from_name'  => null,
        ]);

        $sender = $campaign->sender();

        $this->assertEquals(config('newsletter.fallback.from_email'), $sender['from_email']);
    }

    public function test_scope_due_returns_only_scheduled_past_campaigns(): void
    {
        Campaign::factory()->due()->create();
        Campaign::factory()->scheduled()->create(); // future — should not appear
        Campaign::factory()->draft()->create();
        Campaign::factory()->sent()->create();

        $due = Campaign::due()->get();

        $this->assertCount(1, $due);
        $this->assertEquals('scheduled', $due->first()->status);
    }

    public function test_scope_draft_filters_correctly(): void
    {
        Campaign::factory()->draft()->create();
        Campaign::factory()->sent()->create();

        $this->assertCount(1, Campaign::draft()->get());
    }
}
