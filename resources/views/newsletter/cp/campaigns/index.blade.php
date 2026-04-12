@extends('statamic::layout')
@section('title', 'Campaigns')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold">Campaigns</h1>
    <a href="{{ cp_route('newsletter.campaigns.create') }}"
       class="btn-primary">New Campaign</a>
</div>

{{-- Filters --}}
<form method="GET" class="flex gap-3 mb-6">
    <select name="collection" onchange="this.form.submit()"
            class="input-text text-sm">
        <option value="">All Collections</option>
        @foreach($collections as $value => $label)
            <option value="{{ $value }}" {{ request('collection') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    <select name="status" onchange="this.form.submit()"
            class="input-text text-sm">
        <option value="">All Statuses</option>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if(request()->hasAny(['collection','status']))
        <a href="{{ cp_route('newsletter.campaigns.index') }}"
           class="btn btn-sm">Clear</a>
    @endif
</form>

@if(session('success'))
    <div class="bg-green-100 border border-green-300 text-green-800 rounded p-3 mb-4 text-sm">
        {{ session('success') }}
    </div>
@endif

{{-- Table --}}
<div class="card p-0 overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Collection</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Recipients</th>
                <th>Scheduled / Sent</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $campaign)
            <tr>
                <td class="font-medium">
                    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
                       class="text-blue hover:underline">
                        {{ $campaign->name }}
                    </a>
                </td>
                <td class="text-sm text-grey-70">
                    {{ $campaign->collection === 'insight_newsletters' ? 'Insight' : 'Foundation' }}
                </td>
                <td class="text-sm max-w-xs truncate">{{ $campaign->subject }}</td>
                <td>
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
                    <span class="badge {{ $badge }}">{{ ucfirst($campaign->status) }}</span>
                </td>
                <td class="text-sm text-grey-70">
                    {{ number_format($campaign->total_recipients ?? 0) }}
                </td>
                <td class="text-sm text-grey-70">
                    @if($campaign->scheduled_at && $campaign->status === 'scheduled')
                        {{ $campaign->scheduled_at->format('M j, Y g:i A') }}
                    @elseif($campaign->sent_at)
                        {{ $campaign->sent_at->format('M j, Y') }}
                    @else
                        &mdash;
                    @endif
                </td>
                <td class="text-right">
                    @if(in_array($campaign->status, ['draft','scheduled']))
                        <a href="{{ cp_route('newsletter.campaigns.edit', $campaign) }}"
                           class="text-sm text-blue hover:underline mr-3">Edit</a>
                    @endif
                    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
                       class="text-sm text-blue hover:underline">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-grey-60 py-8">
                    No campaigns yet.
                    <a href="{{ cp_route('newsletter.campaigns.create') }}" class="text-blue hover:underline">
                        Create your first campaign.
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($campaigns->hasPages())
    <div class="mt-4">{{ $campaigns->links() }}</div>
@endif

@endsection
