<?php

namespace App\Widgets;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use App\Models\WebhookLog;
use Statamic\Widgets\Widget;

/**
 * Newsletter dashboard widget.
 *
 * Add to CP dashboard via: Appearance > Configure Dashboard > Add Widget > "newsletter"
 *
 * Configurable options (in dashboard.yaml / CP widget picker):
 *   days: 30          — lookback window for stats
 *   collection: ''    — filter by collection handle (blank = all)
 */
class NewsletterWidget extends Widget
{
    protected static $handle = 'newsletter';

    public function html()
    {
        $days       = (int) ($this->config('days', 30));
        $collection = $this->config('collection', null);

        // Recent campaigns
        $recentCampaigns = Campaign::query()
            ->when($collection, fn ($q) => $q->where('collection', $collection))
            ->whereIn('status', ['sent', 'sending', 'scheduled'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->withCount([
                'sends',
                'sends as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
                'sends as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ])
            ->get();

        // Period totals
        $periodQuery = CampaignSend::query()
            ->where('sent_at', '>=', now()->subDays($days))
            ->when($collection, fn ($q) => $q->whereHas(
                'campaign', fn ($cq) => $cq->where('collection', $collection)
            ));

        $totals = (clone $periodQuery)
            ->selectRaw('
                COUNT(*) as sent,
                SUM(status IN ("delivered","opened","clicked")) as delivered,
                SUM(opened_at IS NOT NULL) as opened,
                SUM(clicked_at IS NOT NULL) as clicked
            ')
            ->first();

        // Subscriber counts
        $subscriberStats = [
            'active'       => Subscriber::where('status', 'active')->count(),
            'unsubscribed' => Subscriber::where('status', 'unsubscribed')->count(),
            'bounced'      => Subscriber::where('status', 'bounced')->count(),
        ];

        // Webhook health (last 24h)
        $webhookFailed = WebhookLog::where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('error')
            ->count();

        return view('newsletter.cp.widgets.newsletter', [
            'days'             => $days,
            'collection'       => $collection,
            'recentCampaigns'  => $recentCampaigns,
            'totals'           => $totals,
            'subscriberStats'  => $subscriberStats,
            'webhookFailed'    => $webhookFailed,
        ]);
    }
}
