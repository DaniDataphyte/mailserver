@extends('statamic::layout')
@section('title', 'Webhook Log')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ cp_route('newsletter.analytics.index') }}" class="text-sm text-grey-60 hover:text-grey-80">&larr; Analytics</a>
        <h1 class="text-3xl font-bold mt-1">Webhook Log</h1>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex gap-3 mb-6">
    <select name="event_type" onchange="this.form.submit()" class="input-text text-sm">
        <option value="">All Event Types</option>
        @foreach($eventTypes as $type)
            <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>
                {{ $type }}
            </option>
        @endforeach
    </select>
    <label class="flex items-center gap-2 text-sm cursor-pointer">
        <input type="checkbox" name="failed" value="1"
               {{ request('failed') ? 'checked' : '' }}
               onchange="this.form.submit()" class="checkbox">
        Show failed only
    </label>
    @if(request()->hasAny(['event_type','failed']))
        <a href="{{ cp_route('newsletter.analytics.webhooks') }}" class="btn btn-sm">Clear</a>
    @endif
</form>

<div class="card p-0 overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Type</th>
                <th>Transaction ID</th>
                <th>Recipient</th>
                <th>Status</th>
                <th>Received</th>
                <th>Processed</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr x-data="{ open: false }">
                <td class="text-xs text-grey-50 font-mono">{{ $log->id }}</td>
                <td>
                    <span class="badge text-xs {{ match(strtolower($log->event_type ?? '')) {
                        'delivery','delivered'      => 'bg-green-lighter text-green-dark',
                        'open','opened'             => 'bg-blue-lighter text-blue-dark',
                        'click','clicked'           => 'bg-purple-lighter text-purple-dark',
                        'bounce','bounced','bouncedhard' => 'bg-red-lighter text-red-dark',
                        'complaint','abuse'         => 'bg-red text-white',
                        'unsubscribe','unsubscribed'=> 'bg-yellow-lighter text-yellow-dark',
                        default                     => 'bg-grey-30 text-grey-80',
                    } }}">
                        {{ $log->event_type ?? '—' }}
                    </span>
                </td>
                <td class="text-xs font-mono text-grey-60">
                    {{ $log->transaction_id ? Str::limit($log->transaction_id, 20) : '—' }}
                </td>
                <td class="text-sm text-grey-70">{{ $log->to_email ?? '—' }}</td>
                <td>
                    @if($log->error)
                        <span class="badge bg-red-lighter text-red-dark text-xs">Failed</span>
                    @elseif($log->processed_at)
                        <span class="badge bg-green-lighter text-green-dark text-xs">Processed</span>
                    @else
                        <span class="badge bg-yellow-lighter text-yellow-dark text-xs">Pending</span>
                    @endif
                </td>
                <td class="text-xs text-grey-50">{{ $log->created_at->format('M j H:i:s') }}</td>
                <td class="text-xs text-grey-50">{{ $log->processed_at?->format('M j H:i:s') ?? '—' }}</td>
                <td>
                    <button @click="open = !open"
                            class="text-xs text-blue hover:underline">
                        Payload
                    </button>
                </td>
            </tr>
            {{-- Expandable payload --}}
            <tr x-data="{ open: false }" style="display:none"
                x-show="open"
                x-ref="payload_{{ $log->id }}">
                <td colspan="8" class="bg-grey-10 p-0">
                    <pre class="text-xs p-4 overflow-x-auto text-grey-70 whitespace-pre-wrap">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @if($log->error)
                    <div class="px-4 pb-3 text-xs text-red">
                        <strong>Error:</strong> {{ $log->error }}
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-grey-60 py-8">No webhook logs found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Fix the Alpine toggle: the payload row needs to share state with the toggle button --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-payload]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.togglePayload);
            if (target) target.style.display = target.style.display === 'none' ? '' : 'none';
        });
    });
});
</script>

@if($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif

@endsection
