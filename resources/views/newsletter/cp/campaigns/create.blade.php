@extends('statamic::layout')
@section('title', 'New Campaign')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ cp_route('newsletter.campaigns.index') }}" class="text-grey-60 hover:text-grey-80">&larr; Campaigns</a>
    <h1 class="text-3xl font-bold">New Campaign</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ cp_route('newsletter.campaigns.store') }}"
      x-data="campaignForm()" x-init="init()">
    @csrf

    <div class="flex gap-6">

        {{-- Main column --}}
        <div class="flex-1 space-y-6">

            {{-- Campaign identity --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-4">Campaign Details</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Internal Name <span class="text-red">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="input-text w-full"
                           placeholder="e.g. Insight Weekly — April 2025">
                    <p class="text-xs text-grey-60 mt-1">For internal reference only. Not shown to subscribers.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Collection <span class="text-red">*</span>
                    </label>
                    <select id="collection-select" name="collection" x-model="collection"
                            class="input-text w-full"
                            onchange="onCollectionChange(this.value)">
                        <option value="">— Select a collection —</option>
                        @foreach($collections as $value => $label)
                            <option value="{{ $value }}" {{ old('collection') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Content Entry
                        <span class="text-xs text-grey-50 font-normal ml-1">(select collection first)</span>
                    </label>
                    <select id="entry-select" name="entry_id"
                            class="input-text w-full"
                            onchange="onEntryChange(this.value)">
                        <option value="">— Select a collection first —</option>
                    </select>
                    <p class="text-xs text-grey-60 mt-1">
                        Link to a Statamic entry to pull subject &amp; content, or leave blank and fill below.
                    </p>
                </div>

                <div class="mb-0">
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Subject Line <span class="text-red">*</span>
                    </label>
                    <input type="text" id="subject-input" name="subject" x-model="subject"
                           class="input-text w-full"
                           placeholder="Email subject shown in the inbox">
                </div>
            </div>

            {{-- Sender overrides --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-1">Sender (optional overrides)</h2>
                <p class="text-sm text-grey-60 mb-4">
                    Leave blank to use the collection defaults
                    <template x-if="collection === 'insight_newsletters'">
                        <span>(newsletter@dataphyte.com)</span>
                    </template>
                    <template x-if="collection === 'foundation_newsletters'">
                        <span>(newsletter@dataphyte.org)</span>
                    </template>
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Name</label>
                        <input type="text" name="from_name" value="{{ old('from_name') }}"
                               class="input-text w-full" placeholder="Dataphyte Insight">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">From Email</label>
                        <input type="email" name="from_email" value="{{ old('from_email') }}"
                               class="input-text w-full" placeholder="newsletter@dataphyte.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-grey-80 mb-1">Reply-To</label>
                        <input type="email" name="reply_to" value="{{ old('reply_to') }}"
                               class="input-text w-full" placeholder="editor@dataphyte.com">
                    </div>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="w-72 space-y-6">

            {{-- Audience --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold mb-3">Audience</h2>

                {{-- Send to all toggle --}}
                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="hidden" name="send_to_all" value="0">
                    <input type="checkbox" name="send_to_all" value="1"
                           x-model="sendToAll"
                           class="toggle-input">
                    <span class="text-sm font-medium">Send to All</span>
                </label>
                <p class="text-xs text-grey-60 mb-4" x-show="sendToAll">
                    Every active subscriber in this collection's group will receive this campaign.
                </p>

                {{-- Sub-group checkboxes --}}
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
                                   class="checkbox">
                            <span class="text-sm">{{ $sub->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Schedule / Send --}}
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
                    <label class="block text-sm font-medium text-grey-80 mb-1">
                        Send at <span class="text-red">*</span>
                    </label>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at') }}"
                           class="input-text w-full text-sm">
                </div>

                <div x-show="action === 'send'"
                     class="bg-red-100 border border-red-300 rounded p-3 mb-4 text-xs text-red-800">
                    This will immediately send to all selected audiences. This cannot be undone.
                </div>

                <button type="submit" class="btn-primary w-full"
                        :class="{ 'bg-red border-red hover:bg-red-dark': action === 'send' }">
                    <span x-text="action === 'send' ? 'Send Campaign' : (action === 'schedule' ? 'Schedule Campaign' : 'Save Draft')"></span>
                </button>
            </div>

        </div>
    </div>
</form>

<script>
// All entries keyed by collection — embedded server-side
const ALL_ENTRIES = @json($entries);

// Restore any old() value after validation failure
const OLD_ENTRY_ID   = '{{ old('entry_id', '') }}';
const OLD_COLLECTION = '{{ old('collection', '') }}';

function onCollectionChange(collection) {
    populateEntries(collection, OLD_COLLECTION === collection ? OLD_ENTRY_ID : '');
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

    // Clear and rebuild options
    select.innerHTML = '<option value="">— No entry linked —</option>';
    entries.forEach(function(entry) {
        const opt   = document.createElement('option');
        opt.value   = entry.id;
        const tag   = entry.blueprint ? '[' + entry.blueprint + '] ' : '';
        opt.text    = tag + (entry.date ? entry.date + ' — ' : '') + entry.title;
        opt.selected = entry.id === selectedId;
        select.appendChild(opt);
    });

    if (entries.length === 0) {
        const opt = document.createElement('option');
        opt.disabled = true;
        opt.text     = '(No published entries for this collection)';
        select.appendChild(opt);
    }
}

// Restore state after form validation failure
document.addEventListener('DOMContentLoaded', function () {
    if (OLD_COLLECTION) {
        document.getElementById('collection-select').value = OLD_COLLECTION;
        populateEntries(OLD_COLLECTION, OLD_ENTRY_ID);
    }
});

// Alpine still handles audience group visibility + schedule radio
function campaignForm() {
    return {
        collection: OLD_COLLECTION,
        sendToAll: {{ old('send_to_all', 0) ? 'true' : 'false' }},
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
