<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* Overview dashboard                                                   */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $collection = $request->input('collection');
        $days       = (int) ($request->input('days', 30));

        // Aggregate stats per campaign (last N days)
        $campaigns = Campaign::query()
            ->when($collection, fn ($q) => $q->where('collection', $collection))
            ->whereIn('status', ['sent', 'sending'])
            ->where('sent_at', '>=', now()->subDays($days))
            ->withCount([
                'sends',
                'sends as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'opened', 'clicked']),
                'sends as opened_count'    => fn ($q) => $q->whereNotNull('opened_at'),
                'sends as clicked_count'   => fn ($q) => $q->whereNotNull('clicked_at'),
                'sends as bounced_count'   => fn ($q) => $q->whereIn('status', ['bounced', 'failed']),
            ])
            ->orderByDesc('sent_at')
            ->get();

        // Totals across the period
        $totals = [
            'campaigns'  => $campaigns->count(),
            'sent'        => $campaigns->sum('sends_count'),
            'delivered'   => $campaigns->sum('delivered_count'),
            'opened'      => $campaigns->sum('opened_count'),
            'clicked'     => $campaigns->sum('clicked_count'),
            'bounced'     => $campaigns->sum('bounced_count'),
        ];

        $totals['delivery_rate'] = $totals['sent'] > 0
            ? round(($totals['delivered'] / $totals['sent']) * 100, 1) : 0;
        $totals['open_rate'] = $totals['delivered'] > 0
            ? round(($totals['opened'] / $totals['delivered']) * 100, 1) : 0;
        $totals['click_rate'] = $totals['opened'] > 0
            ? round(($totals['clicked'] / $totals['opened']) * 100, 1) : 0;

        // Daily send volume for chart (last N days)
        $dailyVolume = CampaignSend::query()
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as total, SUM(opened_at IS NOT NULL) as opened')
            ->where('sent_at', '>=', now()->subDays($days))
            ->when($collection, fn ($q) => $q->whereHas('campaign', fn ($cq) => $cq->where('collection', $collection)))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Webhook health (last 24h)
        $webhookHealth = [
            'total'       => WebhookLog::where('created_at', '>=', now()->subHours(24))->count(),
            'processed'   => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNotNull('processed_at')->count(),
            'failed'      => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNotNull('error')->count(),
            'pending'     => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNull('processed_at')->whereNull('error')->count(),
        ];

        return view('newsletter.cp.analytics.index', compact(
            'campaigns', 'totals', 'dailyVolume', 'webhookHealth', 'days', 'collection'
        ));
    }

    /* ------------------------------------------------------------------ */
    /* Per-campaign analytics detail                                        */
    /* ------------------------------------------------------------------ */

    public function campaign(Campaign $campaign)
    {
        $campaign->load('audiences.targetable');

        // Full stats
        $stats = $campaign->sends()
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(status IN ("delivered","opened","clicked")) as delivered,
                SUM(status IN ("failed","bounced","complained")) as failed,
                SUM(opened_at IS NOT NULL) as opened,
                SUM(clicked_at IS NOT NULL) as clicked,
                SUM(status = "bounced") as bounced,
                SUM(status = "complained") as complained,
                SUM(status IN ("delivered","opened","clicked") AND opened_at IS NULL) as unread
            ')
            ->first()
            ->toArray();

        // Rates
        $stats['delivery_rate'] = $stats['total_sent'] > 0
            ? round(($stats['delivered'] / $stats['total_sent']) * 100, 1) : 0;
        $stats['open_rate'] = $stats['delivered'] > 0
            ? round(($stats['opened'] / $stats['delivered']) * 100, 1) : 0;
        $stats['click_rate'] = $stats['opened'] > 0
            ? round(($stats['clicked'] / $stats['opened']) * 100, 1) : 0;
        $stats['click_to_delivery'] = $stats['delivered'] > 0
            ? round(($stats['clicked'] / $stats['delivered']) * 100, 1) : 0;

        // Hourly open distribution (when did people open it?)
        $opensByHour = $campaign->sends()
            ->selectRaw('HOUR(opened_at) as hour, COUNT(*) as count')
            ->whereNotNull('opened_at')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        // Top clicked links
        $topLinks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->selectRaw('url, COUNT(*) as clicks, COUNT(DISTINCT campaign_send_id) as unique_clicks')
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(15)
            ->get();

        // Status breakdown for pie chart
        $statusBreakdown = $campaign->sends()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Opens over time (cumulative, first 48h after send)
        $opensOverTime = [];
        if ($campaign->sent_at) {
            $opensOverTime = $campaign->sends()
                ->selectRaw('TIMESTAMPDIFF(HOUR, ?, opened_at) as hours_after, COUNT(*) as count', [$campaign->sent_at])
                ->whereNotNull('opened_at')
                ->where('opened_at', '<=', $campaign->sent_at->addHours(48))
                ->groupBy('hours_after')
                ->orderBy('hours_after')
                ->pluck('count', 'hours_after');
        }

        // Failed sends detail
        $failedSends = $campaign->sends()
            ->whereIn('status', ['failed', 'bounced'])
            ->with('subscriber:id,email,first_name,last_name')
            ->limit(50)
            ->get(['id', 'subscriber_id', 'status', 'bounce_reason', 'failed_at', 'bounced_at']);

        return view('newsletter.cp.analytics.campaign', compact(
            'campaign', 'stats', 'opensByHour', 'topLinks',
            'statusBreakdown', 'opensOverTime', 'failedSends'
        ));
    }

    /* ------------------------------------------------------------------ */
    /* Webhook log viewer                                                   */
    /* ------------------------------------------------------------------ */

    public function webhooks(Request $request)
    {
        $query = WebhookLog::query()->latest();

        if ($type = $request->input('event_type')) {
            $query->where('event_type', $type);
        }

        if ($request->input('failed')) {
            $query->failed();
        }

        $logs = $query->paginate(50)->withQueryString();

        $eventTypes = WebhookLog::select('event_type')
            ->distinct()
            ->whereNotNull('event_type')
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('newsletter.cp.analytics.webhooks', compact('logs', 'eventTypes'));
    }
}
