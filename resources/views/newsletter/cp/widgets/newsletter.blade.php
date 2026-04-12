<div class="card p-0 overflow-hidden">

    {{-- Widget header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-grey-20 bg-grey-10">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-grey-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
            </svg>
            <span class="font-semibold text-sm">Newsletter</span>
            <span class="text-xs text-grey-50">last {{ $days }} days</span>
        </div>
        <a href="{{ cp_route('newsletter.analytics.index') }}"
           class="text-xs text-blue hover:underline">Analytics &rarr;</a>
    </div>

    {{-- KPI row --}}
    <div class="grid grid-cols-4 divide-x divide-grey-20 border-b border-grey-20">
        @php
            $sent      = (int) ($totals->sent ?? 0);
            $delivered = (int) ($totals->delivered ?? 0);
            $opened    = (int) ($totals->opened ?? 0);
            $clicked   = (int) ($totals->clicked ?? 0);
            $openRate  = $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0;
            $clickRate = $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0;

            $kpis = [
                ['label' => 'Sent',      'value' => number_format($sent),      'color' => 'text-grey-80'],
                ['label' => 'Delivered', 'value' => number_format($delivered),  'color' => 'text-green-dark'],
                ['label' => 'Open Rate', 'value' => $openRate . '%',            'color' => 'text-blue-dark'],
                ['label' => 'CTR',       'value' => $clickRate . '%',           'color' => 'text-purple-dark'],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="px-4 py-3 text-center">
            <p class="text-xl font-bold {{ $kpi['color'] }}">{{ $kpi['value'] }}</p>
            <p class="text-xs text-grey-50 mt-0.5">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Subscriber summary --}}
    <div class="flex items-center gap-4 px-4 py-2 border-b border-grey-20 text-xs text-grey-60">
        <span>
            <span class="font-semibold text-green-dark">{{ number_format($subscriberStats['active']) }}</span> active
        </span>
        <span>&middot;</span>
        <span>
            <span class="font-semibold text-grey-60">{{ number_format($subscriberStats['unsubscribed']) }}</span> unsubscribed
        </span>
        <span>&middot;</span>
        <span>
            <span class="font-semibold {{ $subscriberStats['bounced'] > 0 ? 'text-red' : 'text-grey-60' }}">
                {{ number_format($subscriberStats['bounced']) }}
            </span> bounced
        </span>
        @if($webhookFailed > 0)
        <span class="ml-auto text-red font-semibold">
            ⚠ {{ $webhookFailed }} webhook error{{ $webhookFailed > 1 ? 's' : '' }}
        </span>
        @endif
    </div>

    {{-- Recent campaigns --}}
    @forelse($recentCampaigns as $campaign)
    @php
        $openRate = $campaign->sends_count > 0
            ? round(($campaign->opened_count / $campaign->sends_count) * 100, 1) : 0;
    @endphp
    <div class="flex items-center gap-3 px-4 py-2 border-b border-grey-20 last:border-0 hover:bg-grey-10 transition-colors">
        <div class="flex-1 min-w-0">
            <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
               class="text-sm font-medium text-blue hover:underline truncate block">
                {{ $campaign->name }}
            </a>
            <p class="text-xs text-grey-50 mt-0.5">
                {{ $campaign->sent_at?->format('M j') ?? ($campaign->scheduled_at?->format('M j') ?? '—') }}
                &middot;
                {{ $campaign->collection === 'insight_newsletters' ? 'Insight' : 'Foundation' }}
            </p>
        </div>
        <div class="flex items-center gap-3 text-xs shrink-0">
            <span class="badge {{ match($campaign->status) {
                'sent'      => 'bg-green-lighter text-green-dark',
                'sending'   => 'bg-blue-lighter text-blue-dark',
                'scheduled' => 'bg-yellow-lighter text-yellow-dark',
                default     => 'bg-grey-30 text-grey-80',
            } }}">{{ $campaign->status }}</span>

            @if($campaign->status === 'sent')
            <span class="text-grey-60">
                {{ number_format($campaign->sends_count) }} sent
                &middot;
                <span class="text-blue-dark font-medium">{{ $openRate }}% open</span>
            </span>
            @endif
        </div>
    </div>
    @empty
    <div class="px-4 py-6 text-center text-sm text-grey-50">
        No campaigns in the last {{ $days }} days.
        <a href="{{ cp_route('newsletter.campaigns.create') }}" class="text-blue hover:underline">Create one &rarr;</a>
    </div>
    @endforelse

    {{-- Footer --}}
    <div class="px-4 py-2 border-t border-grey-20 bg-grey-10 flex justify-between text-xs text-grey-50">
        <a href="{{ cp_route('newsletter.campaigns.index') }}" class="hover:text-blue">All campaigns</a>
        <a href="{{ cp_route('newsletter.subscribers.index') }}" class="hover:text-blue">Subscribers</a>
        <a href="{{ cp_route('newsletter.analytics.webhooks') }}" class="hover:text-blue">Webhook log</a>
    </div>

</div>
