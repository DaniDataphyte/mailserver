<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\AudienceResolver;
use Tests\TestCase;

class AudienceResolverTest extends TestCase
{
    private AudienceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AudienceResolver();
    }

    public function test_resolves_subscribers_from_sub_group(): void
    {
        $group    = SubscriberGroup::factory()->create();
        $subGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);

        $active   = Subscriber::factory()->count(3)->create();
        $inactive = Subscriber::factory()->unsubscribed()->create();

        // Attach active subscribers to the sub-group
        foreach ($active as $sub) {
            $sub->subGroups()->attach($subGroup->id, ['subscribed_at' => now()]);
        }
        // Attach inactive subscriber too
        $inactive->subGroups()->attach($subGroup->id, ['subscribed_at' => now()]);

        $campaign = Campaign::factory()->create();
        CampaignAudience::create([
            'campaign_id'     => $campaign->id,
            'targetable_type' => 'subscriber_sub_group',
            'targetable_id'   => $subGroup->id,
            'send_to_all'     => false,
        ]);

        $campaign->load('audiences');
        $resolved = $this->resolver->resolve($campaign);

        // Only active subscribers should be resolved
        $this->assertCount(3, $resolved);
        $this->assertNotContains($inactive->id, $resolved->pluck('id'));
    }

    public function test_resolves_all_subscribers_from_group(): void
    {
        $group     = SubscriberGroup::factory()->create();
        $subGroup1 = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);
        $subGroup2 = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);

        $subscribers1 = Subscriber::factory()->count(2)->create();
        $subscribers2 = Subscriber::factory()->count(2)->create();

        foreach ($subscribers1 as $sub) {
            $sub->subGroups()->attach($subGroup1->id, ['subscribed_at' => now()]);
        }
        foreach ($subscribers2 as $sub) {
            $sub->subGroups()->attach($subGroup2->id, ['subscribed_at' => now()]);
        }

        $campaign = Campaign::factory()->create();
        CampaignAudience::create([
            'campaign_id'     => $campaign->id,
            'targetable_type' => 'subscriber_group',
            'targetable_id'   => $group->id,
            'send_to_all'     => true,
        ]);

        $campaign->load('audiences');
        $resolved = $this->resolver->resolve($campaign);

        $this->assertCount(4, $resolved);
    }

    public function test_deduplicates_subscribers_across_audiences(): void
    {
        $group    = SubscriberGroup::factory()->create();
        $subGroup1 = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);
        $subGroup2 = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);

        $subscriber = Subscriber::factory()->create();

        // Same subscriber in both sub-groups
        $subscriber->subGroups()->attach($subGroup1->id, ['subscribed_at' => now()]);
        $subscriber->subGroups()->attach($subGroup2->id, ['subscribed_at' => now()]);

        $campaign = Campaign::factory()->create();

        foreach ([$subGroup1->id, $subGroup2->id] as $sgId) {
            CampaignAudience::create([
                'campaign_id'     => $campaign->id,
                'targetable_type' => 'subscriber_sub_group',
                'targetable_id'   => $sgId,
                'send_to_all'     => false,
            ]);
        }

        $campaign->load('audiences');
        $resolved = $this->resolver->resolve($campaign);

        $this->assertCount(1, $resolved);
    }

    public function test_returns_empty_when_no_audiences(): void
    {
        $campaign = Campaign::factory()->create();

        $resolved = $this->resolver->resolve($campaign->load('audiences'));

        $this->assertCount(0, $resolved);
    }
}
