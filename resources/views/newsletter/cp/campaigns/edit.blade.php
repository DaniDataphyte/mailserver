@extends('statamic::layout')
@section('title', 'Edit Campaign')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.campaigns.show', $campaign) }}"
       class="text-grey-60 hover:text-grey-80">&larr; {{ $campaign->name }}</a>
    <h1 class="text-3xl font-bold">Edit Campaign</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ cp_route('newsletter.campaigns.update', $campaign) }}"
      x-data="campaignForm()" x-init="init()">
    @csrf
    @method('PUT')

    <div class="flex gap-6">

        {{-- Main column --}}
        <div class="flex-1 space-y-6">

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Campaign Details</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Internal Name <span class="text-red">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $campaign->name) }}"
                           class="input-text w-full">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Collection <span class="text-red">*</span></label>
                    <select id="collection-select" name="collection" x-model="collection"
                            class="input-text w-full"
                            onchange="onCollectionChange(this.value)">
                        @foreach($collections as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('collection', $campaign->collection) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Content Entry</label>
                    <select id="entry-select" name="entry_id"
                            class="input-text w-full"
                            onchange="onEntryChange(this.value)">
                        <option value="">— No entry linked —</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-grey-80 mb-1">Subject Line <span class="text-red">*</span></label>
                    <input type="text" id="subject-input" name="subject" x-model="subject" class="input-text w-full">
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Sender Overrides</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Name</label>
                        <input type="text" name="from_name"
                               value="{{ old('from_name', $campaign->from_name) }}"
                               class="input-text w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Email</label>
                        <input type="email" name="from_email"
                               value="{{ old('from_email', $campaign->from_email) }}"
                               class="input-text w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">Reply-To</label>
                        <input type="email" name="reply_to"
                               value="{{ old('reply_to', $campaign->reply_to) }}"
                               class="input-text w-full">
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="w-72 space-y-6">

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Audience</h2>

                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="hidden" name="send_to_all" value="0">
                    <input type="checkbox" name="send_to_all" value="1"
                           x-model="sendToAll" class="toggle-input">
                    <span class="text-sm font-medium">Send to All</span>
                </label>

                <div x-show="!sendToAll">
                    @foreach($subGroups as $group)
                    <div class="mb-3" x-show="groupMatchesCollection('{{ $group->slug }}')">
                        <p class="text-xs font-semibold uppercase tracking-wide text-grey-60 mb-1">
                            {{ $group->name }}
                        </p>
                        @foreach($group->subGroups as $sub)
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" name="sub_groups[]"
                                   value="{{ $sub->id }}"
                                   {{ in_array($sub->id, $selectedSubGroupIds) ? 'checked' : '' }}
                                   class="checkbox">
                            <span class="text-sm">{{ $sub->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Send</h2>

                <div class="space-y-2 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="draft"
                               x-model="action" class="radio">
                        <span class="text-sm">Save as Draft</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="schedule"
                               x-model="action" class="radio">
                        <span class="text-sm">Schedule <span class="text-grey-50 font-normal">(pick a future date &darr;)</span></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="action" value="send"
                               x-model="action" class="radio">
                        <span class="text-sm font-medium text-red">Send Now</span>
                    </label>
                </div>

                <div x-show="action === 'schedule'" class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">Send at <span class="text-red">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i')) }}"
                           class="input-text w-full text-sm">
                </div>

                <div x-show="action === 'send'"
                     class="bg-red-100 border border-red-300 rounded p-3 mb-4 text-xs text-red-800">
                    This will immediately send to all selected audiences.
                </div>

                <button type="submit" class="btn-primary w-full"
                        :class="{ 'bg-red border-red': action === 'send' }">
                    <span x-text="action === 'send' ? 'Send Campaign' : (action === 'schedule' ? 'Update Schedule' : 'Save Draft')"></span>
                </button>
            </div>

            {{-- Danger --}}
            @if($campaign->status === 'scheduled')
            <div class="card p-6 border-yellow-400">
                <h2 class="text-sm font-semibold mb-2 text-yellow-dark">Cancel Schedule</h2>
                <form method="POST" action="{{ cp_route('newsletter.campaigns.cancel', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm w-full text-yellow-dark border-yellow-400"
                            onclick="return confirm('Move this campaign back to draft?')">
                        Cancel Schedule
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</form>

<script>
const ALL_ENTRIES    = @json($entries);
const OLD_ENTRY_ID   = '{{ old('entry_id', $campaign->entry_id ?? '') }}';
const OLD_COLLECTION = '{{ old('collection', $campaign->collection) }}';

function onCollectionChange(collection) {
    populateEntries(collection, '');
}

function onEntryChange(entryId) {
    if (!entryId) return;
    const collection = document.getElementById('collection-select').value;
    const entries    = ALL_ENTRIES[collection] || [];
    const entry      = entries.find(e => e.id === entryId);
    if (entry && entry.subject) {
        const subjectInput = document.getElementById('subject-input');
        if (subjectInput && !subjectInput.value) {
            subjectInput.value = entry.subject;
        }
    }
}

function populateEntries(collection, selectedId) {
    const select  = document.getElementById('entry-select');
    const entries = ALL_ENTRIES[collection] || [];
    select.innerHTML = '<option value="">— No entry linked —</option>';
    entries.forEach(function(entry) {
        const opt   = document.createElement('option');
        opt.value   = entry.id;
        opt.text    = (entry.date ? entry.date + ' — ' : '') + entry.title;
        opt.selected = entry.id === selectedId;
        select.appendChild(opt);
    });
    if (entries.length === 0) {
        const opt    = document.createElement('option');
        opt.disabled = true;
        opt.text     = '(No published entries for this collection)';
        select.appendChild(opt);
    }
}

// Populate immediately on page load with the campaign's current values
document.addEventListener('DOMContentLoaded', function () {
    if (OLD_COLLECTION) {
        populateEntries(OLD_COLLECTION, OLD_ENTRY_ID);
    }
});

function campaignForm() {
    return {
        collection: OLD_COLLECTION,
        subject: '{{ old('subject', addslashes($campaign->subject ?? '')) }}',
        sendToAll: {{ $sendToAll ? 'true' : 'false' }},
        action: '{{ old('action', 'draft') }}',

        groupMatchesCollection(groupSlug) {
            if (!this.collection) return true;
            const map = {
                'insight_newsletters': 'insight-subscribers',
                'foundation_newsletters': 'foundation',
            };
            return map[this.collection] === groupSlug;
        },
    }
}
</script>

@endsection
