@extends('statamic::layout')
@section('title', 'Analytics')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Analytics</h1>

    <form method="GET" class="flex gap-3 items-center">
        <select name="collection" onchange="this.form.submit()" class="input-text text-sm">
            <option value="">All Collections</option>
            <option value="insight_newsletters"    {{ $collection === 'insight_newsletters'    ? 'selected' : '' }}>Insight</option>
            <option value="foundation_newsletters" {{ $collection === 'foundation_newsletters' ? 'selected' : '' }}>Foundation</option>
        </select>
        <select name="days" onchange="this.form.submit()" class="input-text text-sm">
            @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $val => $label)
                <option value="{{ $val }}" {{ $days == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Summary KPI cards --}}
<div class="grid grid-cols-4 gap-4 mb-8">
    @php
        $kpis = [
            ['label' => 'Campaigns Sent',  'value' => number_format($totals['campaigns']),  'sub' => "last {$days} days",    'color' => 'text-grey-80'],
            ['label' => 'Delivery Rate',   'value' => $totals['delivery_rate'] . '%',       'sub' => number_format($totals['delivered']) . ' delivered', 'color' => 'text-green-dark'],
            ['label' => 'Open Rate',       'value' => $totals['open_rate'] . '%',            'sub' => number_format($totals['opened']) . ' opened',      'color' => 'text-blue-dark'],
            ['label' => 'Click Rate',      'value' => $totals['click_rate'] . '%',           'sub' => number_format($totals['clicked']) . ' clicked',    'color' => 'text-purple-dark'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="card p-5">
        <p class="text-xs text-grey-50 uppercase tracking-wide mb-1">{{ $kpi['label'] }}</p>
        <p class="text-3xl font-bold {{ $kpi['color'] }} mb-0.5">{{ $kpi['value'] }}</p>
        <p class="text-xs text-grey-50">{{ $kpi['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="flex gap-6">

    {{-- Left: Campaign table + bar chart --}}
    <div class="flex-1 space-y-6">

        {{-- Daily send volume (text-based bar chart — no JS libs needed) --}}
        @if($dailyVolume->isNotEmpty())
        <div class="card p-6">
            <h2 class="font-semibold mb-4">Daily Send Volume</h2>
            @php
                $maxVolume = $dailyVolume->max('total') ?: 1;
                $chartDays = collect();
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $chartDays->push(['date' => $date, 'data' => $dailyVolume->get($date)]);
                }
            @endphp
            <div class="flex items-end gap-0.5 h-24 overflow-hidden">
                @foreach($chartDays as $day)
                @php
                    $total  = $day['data']->total  ?? 0;
                    $opened = $day['data']->opened ?? 0;
                    $height = $maxVolume > 0 ? round(($total / $maxVolume) * 100) : 0;
                    $label  = \Carbon\Carbon::parse($day['date'])->format('M j');
                @endphp
                <div class="flex-1 flex flex-col items-center group relative"
                     title="{{ $label }}: {{ number_format($total) }} sent, {{ number_format($opened) }} opened">
                    <div class="w-full bg-blue-lighter rounded-t"
                         style="height: {{ $height }}%"></div>
                </div>
                @endforeach
            </div>
            <div class="flex justify-between mt-1 text-xs text-grey-40">
                <span>{{ now()->subDays($days - 1)->format('M j') }}</span>
                <span>Today</span>
            </div>
        </div>
        @endif

        {{-- Campaigns table --}}
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20 flex items-center justify-between">
                <h2 class="font-semibold">Campaign Performance</h2>
                <span class="text-xs text-grey-50">{{ $campaigns->count() }} campaigns</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Sent</th>
                        <th>Delivered</th>
                        <th>Open Rate</th>
                        <th>Click Rate</th>
                        <th>Bounced</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    @php
                        $delivRate = $campaign->sends_count > 0
                            ? round(($campaign->delivered_count / $campaign->sends_count) * 100, 1) : 0;
                        $openRate  = $campaign->delivered_count > 0
                            ? round(($campaign->opened_count / $campaign->delivered_count) * 100, 1) : 0;
                        $clickRate = $campaign->opened_count > 0
                            ? round(($campaign->clicked_count / $campaign->opened_count) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ cp_route('newsletter.analytics.campaign', $campaign) }}"
                               class="font-medium text-blue hover:underline">
                                {{ $campaign->name }}
                            </a>
                            <p class="text-xs text-grey-50 mt-0.5">
                                {{ $campaign->sent_at?->format('M j, Y') }}
                                &middot;
                                {{ $campaign->collection === 'insight_newsletters' ? 'Insight' : 'Foundation' }}
                            </p>
                        </td>
                        <td class="text-sm">{{ number_format($campaign->sends_count) }}</td>
                        <td class="text-sm">
                            {{ number_format($campaign->delivered_count) }}
                            <span class="text-xs text-grey-50">({{ $delivRate }}%)</span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-grey-20 rounded-full h-1.5">
                                    <div class="bg-blue h-1.5 rounded-full" style="width:{{ min($openRate, 100) }}%"></div>
                                </div>
                                <span class="text-sm">{{ $openRate }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-grey-20 rounded-full h-1.5">
                                    <div class="bg-purple h-1.5 rounded-full" style="width:{{ min($clickRate, 100) }}%"></div>
                                </div>
                                <span class="text-sm">{{ $clickRate }}%</span>
                            </div>
                        </td>
                        <td class="text-sm {{ $campaign->bounced_count > 0 ? 'text-red' : 'text-grey-60' }}">
                            {{ number_format($campaign->bounced_count) }}
                        </td>
                        <td>
                            <a href="{{ cp_route('newsletter.analytics.campaign', $campaign) }}"
                               class="text-xs text-blue hover:underline">Details &rarr;</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-grey-60 py-8">
                            No sent campaigns in the last {{ $days }} days.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- Sidebar: webhook health + quick stats --}}
    <div class="w-64 space-y-6">

        <div class="card p-5">
            <h2 class="font-semibold mb-3 text-sm">Webhook Health <span class="text-grey-50 font-normal">(24h)</span></h2>
            @php
                $wh = $webhookHealth;
                $successRate = $wh['total'] > 0 ? round((($wh['processed']) / $wh['total']) * 100) : 100;
            @endphp
            <div class="flex items-center gap-2 mb-3">
                <div class="w-full bg-grey-20 rounded-full h-2">
                    <div class="bg-green h-2 rounded-full transition-all"
                         style="width:{{ $successRate }}%"></div>
                </div>
                <span class="text-sm font-semibold text-green-dark whitespace-nowrap">{{ $successRate }}%</span>
            </div>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-grey-60">Total received</span>
                    <span class="font-medium">{{ number_format($wh['total']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-grey-60">Processed</span>
                    <span class="font-medium text-green-dark">{{ number_format($wh['processed']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-grey-60">Pending</span>
                    <span class="font-medium text-yellow-dark">{{ number_format($wh['pending']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-grey-60">Failed</span>
                    <span class="font-medium {{ $wh['failed'] > 0 ? 'text-red' : 'text-grey-40' }}">
                        {{ number_format($wh['failed']) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-grey-20">
                <a href="{{ cp_route('newsletter.analytics.webhooks') }}"
                   class="text-xs text-blue hover:underline">View webhook log &rarr;</a>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold mb-3 text-sm">Total Activity</h2>
            <div class="space-y-2 text-sm">
                @php
                    $rows = [
                        ['Emails Sent',    number_format($totals['sent'])],
                        ['Delivered',      number_format($totals['delivered'])],
                        ['Opened',         number_format($totals['opened'])],
                        ['Clicked',        number_format($totals['clicked'])],
                        ['Bounced/Failed', number_format($totals['bounced'])],
                    ];
                @endphp
                @foreach($rows as [$label, $val])
                <div class="flex justify-between">
                    <span class="text-grey-60">{{ $label }}</span>
                    <span class="font-medium">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@endsection
