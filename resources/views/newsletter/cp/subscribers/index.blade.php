@extends('statamic::layout')
@section('title', 'Subscribers')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Subscribers</h1>
        <div class="flex gap-2">
            <a href="{{ cp_route('newsletter.subscribers.import.form') }}"
               class="btn-default">Import CSV</a>
            <a href="{{ cp_route('newsletter.subscribers.export') . '?' . http_build_query(request()->only('status','sub_group')) }}"
               class="btn-default">Export CSV</a>
            <a href="{{ cp_route('newsletter.subscribers.create') }}"
               class="btn-primary">Add Subscriber</a>
        </div>
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-4 p-4 rounded-lg {{ $result['skipped'] > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200' }}">
            <p class="font-medium">Import complete: {{ $result['imported'] }} imported, {{ $result['skipped'] }} skipped.</p>
            @if(count($result['errors']))
                <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                    @foreach(array_slice($result['errors'], 0, 10) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    @if(count($result['errors']) > 10)
                        <li>...and {{ count($result['errors']) - 10 }} more</li>
                    @endif
                </ul>
            @endif
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Email or name…"
                   class="input-text w-56">
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Status</label>
            <select name="status" class="input-text">
                <option value="">All statuses</option>
                @foreach(['active','unsubscribed','bounced','complained'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Sub-group</label>
            <select name="sub_group" class="input-text">
                <option value="">All groups</option>
                @foreach($subGroups->groupBy('group.name') as $groupName => $subs)
                    <optgroup label="{{ $groupName }}">
                        @foreach($subs as $subGroup)
                            <option value="{{ $subGroup->id }}" @selected(request('sub_group') == $subGroup->id)>
                                {{ $subGroup->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        @if(request()->hasAny(['search','status','sub_group']))
            <a href="{{ cp_route('newsletter.subscribers.index') }}" class="btn-default">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card p-0 overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Sub-groups</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr>
                        <td>
                            <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
                               class="text-blue font-medium hover:underline">
                                {{ $subscriber->email }}
                            </a>
                        </td>
                        <td>{{ $subscriber->full_name }}</td>
                        <td>
                            <span class="badge-sm
                                {{ $subscriber->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($subscriber->status) }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-600">
                            {{ $subscriber->subGroups->pluck('name')->implode(', ') ?: '—' }}
                        </td>
                        <td class="text-sm text-gray-500">
                            {{ $subscriber->created_at->format('d M Y') }}
                        </td>
                        <td class="text-right">
                            <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}"
                               class="text-sm text-blue hover:underline mr-3">Edit</a>
                            <form method="POST"
                                  action="{{ cp_route('newsletter.subscribers.destroy', $subscriber) }}"
                                  class="inline"
                                  onsubmit="return confirm('Delete this subscriber?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 py-8">No subscribers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subscribers->links() }}
    </div>
@endsection
