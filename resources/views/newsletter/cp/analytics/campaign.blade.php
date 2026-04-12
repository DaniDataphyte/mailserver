@extends('statamic::layout')
@section('title', $campaign->name . ' — Analytics')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.analytics.index') }}" class="text-grey-60 hover:text-grey-80 text-sm">&larr; Analytics</a>
    <div>
        <h1 class="text-3xl font-bold">{{ $campaign->name }}</h1>
        <p class="text-sm text-grey-60 mt-0.5">
            {{ $campaign->collection === 'insight_newsletters' ? 'Dataphyte Insight' : 'Dataphyte Foundation' }}
            &middot; Sent {{ $campaign->sent_at?->format('M j, Y g:i A') ?? '—' }}
        </p>
    </div>
</div>

{{-- KPI Row --}}
<div class="grid grid-cols-5 gap-4 mb-8">
    @php
        $kpis = [
            ['label' => 'Total Sent',    'value' => number_format($stats['total_sent']),  'sub' => '',                                       'color' => 'text-grey-80'],
            ['label' => 'Delivery Rate', 'value' => $stats['delivery_rate'] . '%',        'sub' => number_format($stats['delivered']) . ' emails', 'color' => 'text-green-dark'],
            ['label' => 'Open Rate',     'value' => $stats['open_rate'] . '%',             'sub' => number_format($stats['opened']) . ' opened',    'color' => 'text-blue-dark'],
            ['label' => 'Click Rate',    'value' => $stats['click_rate'] . '%',            'sub' => number_format($stats['clicked']) . ' clicks',   'color' => 'text-purple-dark'],
            ['label' => 'Bounced',       'value' => number_format($stats['bounced']),      'sub' => number_format($stats['failed']) . ' failed',    'color' => $stats['bounced'] > 0 ? 'text-red-dark' : 'text-grey-60'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="card p-4">
        <p class="text-xs text-grey-50 uppercase tracking-wide mb-1">{{ $kpi['label'] }}</p>
        <p class="text-2xl font-bold {{ $kpi['color'] }} mb-0.5">{{ $kpi['value'] }}</p>
        @if($kpi['sub'])<p class="text-xs text-grey-50">{{ $kpi['sub'] }}</p>@endif
    </div>
    @endforeach
</div>

<div class="flex gap-6">

    {{-- Left column --}}
    <div class="flex-1 space-y-6">

        {{-- Opens over time (0–48h after send) --}}
        @if($opensOverTime->isNotEmpty())
        <div class="card p-6">
            <h2 class="font-semibold mb-4">Opens Over Time <span class="text-xs text-grey-50 font-normal">(first 48 hours)</span></h2>
            @php
                $maxOpens = $opensOverTime->max() ?: 1;
                $cumulative = 0;
            @endphp
            <div class="flex items-end gap-0.5 h-20">
                @for($h = 0; $h <= 48; $h++)
                @php
                    $count  = $opensOverTime->get($h, 0);
                    $pct    = round(($count / $maxOpens) * 100);
                @endphp
                <div class="flex-1 bg-blue-lighter rounded-t"
                     style="height:{{ $pct }}%"
                     title="{{ $h }}h: {{ number_format($count) }} opens"></div>
                @endfor
            </div>
            <div class="flex justify-between mt-1 text-xs text-grey-40">
                <span>0h</span><span>12h</span><span>24h</span><span>36h</span><span>48h</span>
            </div>
        </div>
        @endif

        {{-- Status breakdown --}}
        <div class="card p-6">
            <h2 class="font-semibold mb-4">Status Breakdown</h2>
            @php
                $total = $stats['total_sent'] ?: 1;
                $rows = [
                    ['status' => 'clicked',   'label' => 'Clicked',    'count' => $stats['clicked'],   'color' => 'bg-purple'],
                    ['status' => 'opened',    'label' => 'Opened',     'count' => $stats['opened'],    'color' => 'bg-blue'],
                    ['status' => 'delivered', 'label' => 'Unread',     'count' => $stats['unread'],    'color' => 'bg-green-lighter'],
                    ['status' => 'bounced',   'label' => 'Bounced',    'count' => $stats['bounced'],   'color' => 'bg-orange'],
                    ['status' => 'failed',    'label' => 'Failed',     'count' => $stats['failed'],    'color' => 'bg-red-lighter'],
                    ['status' => 'complained','label' => 'Complained', 'count' => $stats['complained'],'color' => 'bg-red'],
                ];
            @endphp
            <div class="space-y-3">
                @foreach($rows as $row)
                @php $pct = round(($row['count'] / $total) * 100, 1); @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-grey-70">{{ $row['label'] }}</span>
                        <span class="font-medium">{{ number_format($row['count']) }} <span class="text-grey-50 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="w-full bg-grey-20 rounded-full h-2">
                        <div class="{{ $row['color'] }} h-2 rounded-full" style="width:{{ min($pct, 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Top clicked links --}}
        @if($topLinks->isNotEmpty())
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20">
                <h2 class="font-semibold">Top Clicked Links</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th class="text-right">Total Clicks</th>
                        <th class="text-right">Unique</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topLinks as $link)
                    <tr>
                        <td class="text-sm">
                            <a href="{{ $link->url }}" target="_blank"
                               class="text-blue hover:underline truncate block max-w-sm"
                               title="{{ $link->url }}">
                                {{ parse_url($link->url, PHP_URL_HOST) }}{{ parse_url($link->url, PHP_URL_PATH) }}
                            </a>
                        </td>
                        <td class="text-sm text-right font-medium">{{ number_format($link->clicks) }}</td>
                        <td class="text-sm text-right text-grey-60">{{ number_format($link->unique_clicks) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Failed / Bounced sends --}}
        @if($failedSends->isNotEmpty())
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20">
                <h2 class="font-semibold text-red">Failed &amp; Bounced <span class="text-grey-50 text-sm font-normal">(up to 50)</span></h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Subscriber</th><th>Status</th><th>Reason</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @foreach($failedSends as $send)
                    <tr>
                        <td class="text-sm">
                            @if($send->subscriber)
                                <a href="{{ cp_route('newsletter.subscribers.show', $send->subscriber) }}"
                                   class="text-blue hover:underline">{{ $send->subscriber->email }}</a>
                            @else
                                <span class="text-grey-50">(deleted)</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-xs {{ $send->status === 'bounced' ? 'bg-red-lighter text-red-dark' : 'bg-orange-lighter text-orange-dark' }}">
                                {{ $send->status }}
                            </span>
                        </td>
                        <td class="text-xs text-grey-60 max-w-xs truncate">{{ $send->bounce_reason ?? '—' }}</td>
                        <td class="text-xs text-grey-50">{{ ($send->bounced_at ?? $send->failed_at)?->format('M j H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="w-64 space-y-6">

        {{-- Audience --}}
        <div class="card p-5 text-sm">
            <h2 class="font-semibold mb-3">Audiences</h2>
            @forelse($campaign->audiences as $audience)
                @if($audience->targetable)
                <div class="flex items-center gap-2 py-1">
                    <span class="w-2 h-2 rounded-full bg-blue inline-block flex-shrink-0"></span>
                    <span>{{ $audience->targetable->name }}</span>
                </div>
                @endif
            @empty
                <p class="text-grey-50">None</p>
            @endforelse
        </div>

        {{-- Opens by hour of day --}}
        @if($opensByHour->isNotEmpty())
        <div class="card p-5">
            <h2 class="font-semibold mb-3 text-sm">Opens by Hour of Day</h2>
            @php $maxByHour = $opensByHour->max() ?: 1; @endphp
            <div class="flex items-end gap-px h-14">
                @for($h = 0; $h < 24; $h++)
                @php $cnt = $opensByHour->get($h, 0); $pct = round(($cnt / $maxByHour) * 100); @endphp
                <div class="flex-1 bg-blue-lighter rounded-t"
                     style="height:{{ $pct }}%"
                     title="{{ $h }}:00 — {{ $cnt }} opens"></div>
                @endfor
            </div>
            <div class="flex justify-between text-xs text-grey-40 mt-1">
                <span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>12am</span>
            </div>
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="card p-5 text-sm space-y-2">
            <h2 class="font-semibold mb-2">Actions</h2>
            <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
               class="block text-blue hover:underline">View campaign &rarr;</a>
        </div>

    </div>
</div>

@endsection
