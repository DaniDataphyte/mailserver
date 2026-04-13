@extends('statamic::layout')
@section('title', $campaign->name)

@section('content')

{{-- Header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <div class="mb-1">
            <a href="{{ cp_route('newsletter.campaigns.index') }}"
               class="text-sm text-grey-60 hover:text-grey-80">&larr; All Campaigns</a>
        </div>
        <h1 class="text-3xl font-bold">{{ $campaign->name }}</h1>
        <p class="text-grey-60 text-sm mt-1">
            {{ $campaign->collection === 'insight_newsletters' ? 'Dataphyte Insight' : 'Dataphyte Foundation' }}
            &nbsp;&middot;&nbsp;
            Created {{ $campaign->created_at->format('M j, Y') }}
        </p>
    </div>

    <div class="flex items-center gap-3">
        @php
            $badge = match($campaign->status) {
                'draft'     => 'bg-grey-30 text-grey-80',
                'scheduled' => 'bg-yellow-lighter text-yellow-dark',
                'sending'   => 'bg-blue-lighter text-blue-dark',
                'sent'      => 'bg-green-lighter text-green-dark',
                'failed'    => 'bg-red-lighter text-red-dark',
                default     => 'bg-grey-30 text-grey-80',
            };
        @endphp
        <span class="badge {{ $badge }} text-sm px-3 py-1">{{ ucfirst($campaign->status) }}</span>

        @if(in_array($campaign->status, ['draft','scheduled']))
            <a href="{{ cp_route('newsletter.campaigns.edit', $campaign) }}"
               class="btn">Edit</a>
        @endif

        {{-- Preview in browser --}}
        <a href="{{ cp_route('newsletter.campaigns.preview', $campaign) }}"
           target="_blank"
           class="btn">
            Preview &rarr;
        </a>

        {{-- Test send --}}
        <div class="relative">
            <button onclick="document.getElementById('test-send-panel').classList.toggle('hidden')"
                    class="btn btn-primary" type="button">
                Send Test Email &hellip;
            </button>
            <div id="test-send-panel"
                 class="hidden absolute right-0 top-full mt-1 w-80 bg-white border border-grey-20 rounded shadow-lg z-50 p-4">
                <p class="text-sm font-medium mb-1">Send test email to:</p>
                <p class="text-xs text-grey-50 mb-3">
                    Merge tags (<code>@{{first_name}}</code>, <code>@{{full_name}}</code>, <code>@{{email}}</code>) are replaced with real subscriber data if the address matches a subscriber, otherwise blank.
                </p>
                <form method="POST"
                      action="{{ cp_route('newsletter.campaigns.test-send', $campaign) }}">
                    @csrf
                    <input type="email" name="email"
                           placeholder="recipient@example.com"
                           class="input-text w-full text-sm mb-2"
                           required>
                    <button type="submit" class="btn-primary w-full text-sm">
                        Send Test
                    </button>
                </form>
            </div>
        </div>

        @if(in_array($campaign->status, ['draft','scheduled']))
            <form method="POST" action="{{ cp_route('newsletter.campaigns.send', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Send this campaign now to all selected audiences?')"
                        class="btn-primary bg-red border-red hover:bg-red-dark">
                    Send Now
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-300 text-green-800 rounded p-3 mb-6 text-sm">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-6 text-sm">
        {{ session('error') }}
    </div>
@endif

<div class="flex gap-6">

    {{-- Left: stats + sends --}}
    <div class="flex-1 space-y-6">

        {{-- Stats cards --}}
        <div class="grid grid-cols-4 gap-4">
            @php
                $statCards = [
                    ['label' => 'Sent',      'value' => $stats['total_sent']      ?? 0, 'color' => 'text-grey-80'],
                    ['label' => 'Delivered', 'value' => $stats['total_delivered'] ?? 0, 'color' => 'text-green-dark'],
                    ['label' => 'Opened',    'value' => $stats['total_opened']    ?? 0, 'color' => 'text-blue-dark'],
                    ['label' => 'Failed',    'value' => $stats['total_failed']    ?? 0, 'color' => 'text-red-dark'],
                ];
            @endphp
            @foreach($statCards as $card)
            <div class="card p-4 text-center">
                <p class="text-3xl font-bold {{ $card['color'] }}">
                    {{ number_format($card['value']) }}
                </p>
                <p class="text-sm text-grey-60 mt-1">{{ $card['label'] }}</p>
                @if($stats['total_sent'] > 0 && in_array($card['label'], ['Delivered','Opened','Failed']))
                <p class="text-xs text-grey-50 mt-0.5">
                    {{ round(($card['value'] / $stats['total_sent']) * 100, 1) }}%
                </p>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Sends --}}
        @if($sends->isNotEmpty())
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b border-grey-20 flex items-center justify-between">
                <h2 class="font-semibold">Sends</h2>
                <span class="text-xs text-grey-50">
                    {{ number_format($sends->total()) }} total
                    &nbsp;&middot;&nbsp;
                    page {{ $sends->currentPage() }} of {{ $sends->lastPage() }}
                </span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subscriber</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Opened</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sends as $send)
                    <tr>
                        <td class="text-sm">
                            @if($send->subscriber)
                                <a href="{{ cp_route('newsletter.subscribers.show', $send->subscriber) }}"
                                   class="text-blue hover:underline">
                                    {{ $send->subscriber->full_name }}
                                </a>
                                <span class="text-grey-50 text-xs ml-1">{{ $send->subscriber->email }}</span>
                            @else
                                <span class="text-grey-50">(deleted)</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-xs {{ match($send->status) {
                                'sent','delivered' => 'bg-green-lighter text-green-dark',
                                'opened','clicked' => 'bg-blue-lighter text-blue-dark',
                                'failed','bounced' => 'bg-red-lighter text-red-dark',
                                default            => 'bg-grey-30 text-grey-80',
                            } }}">{{ $send->status }}</span>
                        </td>
                        <td class="text-sm text-grey-60">{{ $send->sent_at?->format('M j H:i') ?? '—' }}</td>
                        <td class="text-sm text-grey-60">{{ $send->opened_at?->format('M j H:i') ?? '—' }}</td>
                        <td class="text-xs text-grey-50 font-mono truncate max-w-xs">
                            {{ $send->elastic_email_transaction_id ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($sends->hasPages())
            <div class="p-4 border-t border-grey-20 flex items-center justify-between">
                <span class="text-xs text-grey-50">
                    Showing {{ $sends->firstItem() }}–{{ $sends->lastItem() }} of {{ number_format($sends->total()) }}
                </span>
                <div class="flex items-center gap-1">
                    @if($sends->onFirstPage())
                        <span class="btn btn-sm opacity-40 cursor-not-allowed">&laquo; Prev</span>
                    @else
                        <a href="{{ $sends->previousPageUrl() }}" class="btn btn-sm">&laquo; Prev</a>
                    @endif

                    @foreach($sends->getUrlRange(max(1, $sends->currentPage()-2), min($sends->lastPage(), $sends->currentPage()+2)) as $page => $url)
                        @if($page === $sends->currentPage())
                            <span class="btn btn-sm btn-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($sends->hasMorePages())
                        <a href="{{ $sends->nextPageUrl() }}" class="btn btn-sm">Next &raquo;</a>
                    @else
                        <span class="btn btn-sm opacity-40 cursor-not-allowed">Next &raquo;</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="w-72 space-y-6">

        {{-- Campaign info --}}
        <div class="card p-6 text-sm space-y-3">
            <h2 class="font-semibold text-base mb-2">Details</h2>

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Subject</p>
                <p class="font-medium">{{ $campaign->subject }}</p>
            </div>

            @if($entry)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Content Entry</p>
                <a href="{{ cp_route('collections.entries.edit', [$campaign->collection, $campaign->entry_id]) }}"
                   target="_blank"
                   class="text-blue hover:underline">
                    {{ $entry->get('title') ?: $entry->get('subject') ?: 'View Entry' }} &rarr;
                </a>
            </div>
            @endif

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">From</p>
                @php $sender = $campaign->sender(); @endphp
                <p>{{ $sender['from_name'] }}</p>
                <p class="text-grey-60">{{ $sender['from_email'] }}</p>
            </div>

            @if($campaign->scheduled_at)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Scheduled</p>
                <p>{{ $campaign->scheduled_at->format('M j, Y g:i A') }}</p>
            </div>
            @endif

            @if($campaign->sent_at)
            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Sent</p>
                <p>{{ $campaign->sent_at->format('M j, Y g:i A') }}</p>
            </div>
            @endif

            <div>
                <p class="text-xs text-grey-50 uppercase tracking-wide mb-0.5">Total Recipients</p>
                <p>{{ number_format($campaign->total_recipients ?? 0) }}</p>
            </div>
        </div>

        {{-- Audiences --}}
        <div class="card p-6 text-sm">
            <h2 class="font-semibold text-base mb-3">Audiences</h2>
            @forelse($campaign->audiences as $audience)
                @if($audience->targetable)
                <div class="flex items-center gap-2 py-1">
                    <span class="w-2 h-2 rounded-full bg-blue inline-block"></span>
                    <span>{{ $audience->targetable->name }}</span>
                    @if($audience->send_to_all)
                        <span class="text-xs text-grey-50">(all)</span>
                    @endif
                </div>
                @endif
            @empty
                <p class="text-grey-50">No audiences assigned.</p>
            @endforelse
        </div>

        {{-- Reset stuck campaign --}}
        @if(in_array($campaign->status, ['sending','failed']))
        <div class="card p-6 border-yellow-400">
            <h2 class="text-sm font-semibold text-yellow-dark mb-2">Stuck?</h2>
            <p class="text-xs text-grey-60 mb-3">If this campaign is stuck in "{{ $campaign->status }}" (e.g. queue worker not running), reset it to draft and send again.</p>
            <form method="POST" action="{{ cp_route('newsletter.campaigns.reset', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Reset this campaign back to draft?')"
                        class="btn btn-sm w-full text-yellow-dark border-yellow-400">
                    Reset to Draft
                </button>
            </form>
        </div>
        @endif

        {{-- Danger zone --}}
        @if(in_array($campaign->status, ['draft','scheduled']))
        <div class="card p-6 border-red-300">
            <h2 class="text-sm font-semibold text-red mb-3">Danger Zone</h2>
            <form method="POST" action="{{ cp_route('newsletter.campaigns.destroy', $campaign) }}">
                @csrf @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Permanently delete this campaign?')"
                        class="btn btn-sm w-full text-red border-red-300 hover:bg-red-50">
                    Delete Campaign
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

<script>
// Close test-send panel when clicking outside it
document.addEventListener('click', function (e) {
    var panel  = document.getElementById('test-send-panel');
    var toggle = e.target.closest('[onclick*="test-send-panel"]');
    if (panel && !panel.contains(e.target) && !toggle) {
        panel.classList.add('hidden');
    }
});
</script>

@endsection
