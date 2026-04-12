@extends('statamic::layout')
@section('title', $subscriber->email)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ cp_route('newsletter.subscribers.index') }}"
               class="text-sm text-gray-500 hover:underline mb-1 block">← Subscribers</a>
            <h1 class="text-3xl font-bold">{{ $subscriber->full_name }}</h1>
            <p class="text-gray-500 mt-1">{{ $subscriber->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}"
               class="btn-primary">Edit</a>
            <a href="{{ cp_route('newsletter.subscribers.gdpr.export', $subscriber) }}"
               class="btn" title="Download all personal data (GDPR export)">
                Export Data
            </a>
            @if($subscriber->status !== 'erased')
            <a href="{{ cp_route('newsletter.subscribers.gdpr.erase-form', $subscriber) }}"
               class="btn text-red border-red-300 hover:bg-red-50"
               title="Right to erasure (GDPR Art. 17)">
                Erase
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Stats --}}
        @foreach([
            ['label' => 'Total Sent',      'value' => $stats['total_sent']      ?? 0],
            ['label' => 'Delivered',        'value' => $stats['total_delivered'] ?? 0],
            ['label' => 'Opened',           'value' => $stats['total_opened']    ?? 0],
            ['label' => 'Failed / Bounced', 'value' => $stats['total_failed']    ?? 0],
        ] as $stat)
            <div class="card p-4 text-center">
                <div class="text-2xl font-bold text-blue">{{ $stat['value'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Subscriber info --}}
        <div class="card p-4">
            <h2 class="font-semibold mb-3 text-gray-700">Details</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="font-medium {{ $subscriber->status === 'active' ? 'text-green-600' : 'text-red-500' }}">
                            {{ ucfirst($subscriber->status) }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Added</dt>
                    <dd>{{ $subscriber->created_at->format('d M Y') }}</dd>
                </div>
                @if($subscriber->unsubscribed_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Unsubscribed</dt>
                        <dd>{{ $subscriber->unsubscribed_at->format('d M Y') }}</dd>
                    </div>
                @endif
            </dl>

            <h2 class="font-semibold mt-5 mb-3 text-gray-700">Sub-groups</h2>
            <ul class="text-sm space-y-1">
                @forelse($subscriber->subGroups as $sg)
                    <li class="flex justify-between">
                        <span>{{ $sg->name }}</span>
                        <span class="text-gray-400 text-xs">{{ $sg->group->name }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">None</li>
                @endforelse
            </ul>
        </div>

        {{-- Send history --}}
        <div class="card p-4 md:col-span-2">
            <h2 class="font-semibold mb-3 text-gray-700">Campaign History</h2>
            @if($sendHistory->count())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="pb-2">Campaign</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sendHistory as $send)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $send->campaign->name }}</td>
                                <td class="py-2">
                                    <span class="capitalize text-xs font-medium
                                        {{ $send->status === 'opened' ? 'text-green-600' : '' }}
                                        {{ in_array($send->status, ['bounced','failed']) ? 'text-red-500' : '' }}">
                                        {{ $send->status }}
                                    </span>
                                </td>
                                <td class="py-2 text-gray-400">
                                    {{ $send->sent_at?->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">{{ $sendHistory->links() }}</div>
            @else
                <p class="text-gray-400 text-sm">No campaigns sent yet.</p>
            @endif
        </div>
    </div>
@endsection
