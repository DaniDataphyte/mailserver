@extends('statamic::layout')
@section('title', 'Edit Group')

@section('content')
    <div class="mb-6">
        <a href="{{ cp_route('newsletter.groups.index') }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← Groups</a>
        <h1 class="text-3xl font-bold">{{ $group->name }}</h1>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Edit group --}}
        <div>
            <h2 class="font-semibold mb-3">Group Details</h2>
            <form method="POST" action="{{ cp_route('newsletter.groups.update', $group) }}" class="card p-5 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="publish-field-label">Group Name</label>
                    <input type="text" name="name" value="{{ old('name', $group->name) }}"
                           class="input-text w-full" required>
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="publish-field-label">Description</label>
                    <textarea name="description" rows="2"
                              class="input-text w-full">{{ old('description', $group->description) }}</textarea>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>

        {{-- Sub-groups --}}
        <div>
            <h2 class="font-semibold mb-3">Sub-groups</h2>
            <div class="card p-5 space-y-3">
                @forelse($group->subGroups as $subGroup)
                    <div class="flex items-center justify-between py-1" x-data="{ editing: false }">
                        <div x-show="!editing">
                            <span class="font-medium text-sm">{{ $subGroup->name }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $subGroup->subscribers_count }} subscribers</span>
                        </div>

                        {{-- Inline edit --}}
                        <form x-show="editing"
                              method="POST"
                              action="{{ cp_route('newsletter.groups.sub-groups.update', [$group, $subGroup]) }}">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $subGroup->name }}"
                                   class="input-text text-sm py-1 px-2 w-36" required>
                            <button type="submit" class="text-xs text-blue ml-1">Save</button>
                        </form>

                        <div class="flex gap-2 text-xs">
                            <button @click="editing = !editing" class="text-blue hover:underline">
                                <span x-text="editing ? 'Cancel' : 'Rename'"></span>
                            </button>
                            <form method="POST"
                                  action="{{ cp_route('newsletter.groups.sub-groups.destroy', [$group, $subGroup]) }}"
                                  onsubmit="return confirm('Delete sub-group {{ $subGroup->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No sub-groups yet.</p>
                @endforelse

                {{-- Add sub-group --}}
                <form method="POST"
                      action="{{ cp_route('newsletter.groups.sub-groups.store', $group) }}"
                      class="mt-3 pt-3 border-t flex gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="New sub-group name"
                           class="input-text flex-1 text-sm" required>
                    <button type="submit" class="btn-primary text-sm">Add</button>
                </form>
            </div>
        </div>

    </div>
@endsection
