<?php

namespace App\Services\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Collection;

/**
 * Resolves the final deduplicated list of active subscriber IDs
 * for a given campaign, based on its CampaignAudience rows.
 *
 * Audience rows can target:
 *   • SubscriberGroup  (send_to_all = true  → entire group)
 *   • SubscriberSubGroup (send_to_all = false → specific sub-group)
 *
 * Only 'active' subscribers are included.
 */
class AudienceResolver
{
    /**
     * Returns a deduplicated Collection of Subscriber models.
     *
     * @param  Campaign  $campaign
     * @return Collection<int, Subscriber>
     */
    public function resolve(Campaign $campaign): Collection
    {
        $subscriberIds = collect();

        foreach ($campaign->audiences as $audience) {
            $ids = $this->resolveAudienceRow($audience);
            $subscriberIds = $subscriberIds->merge($ids);
        }

        // Deduplicate, then load the full Subscriber models
        $uniqueIds = $subscriberIds->unique()->values();

        if ($uniqueIds->isEmpty()) {
            return collect();
        }

        return Subscriber::active()
            ->whereIn('id', $uniqueIds)
            ->get();
    }

    /**
     * Returns subscriber IDs for a single audience row.
     */
    private function resolveAudienceRow(CampaignAudience $audience): Collection
    {
        $target = $audience->targetable;

        if (! $target) {
            return collect();
        }

        // Target is an entire group (send_to_all)
        if ($target instanceof SubscriberGroup) {
            return $this->resolveGroup($target);
        }

        // Target is a specific sub-group
        if ($target instanceof SubscriberSubGroup) {
            return $this->resolveSubGroup($target);
        }

        return collect();
    }

    private function resolveGroup(SubscriberGroup $group): Collection
    {
        return Subscriber::active()
            ->whereHas('allSubGroups', function ($q) use ($group) {
                $q->where('subscriber_group_id', $group->id)
                  ->whereNull('subscriber_sub_group.unsubscribed_at');
            })
            ->pluck('id');
    }

    private function resolveSubGroup(SubscriberSubGroup $subGroup): Collection
    {
        return Subscriber::active()
            ->whereHas('subGroups', function ($q) use ($subGroup) {
                $q->where('subscriber_sub_groups.id', $subGroup->id);
            })
            ->pluck('id');
    }
}
