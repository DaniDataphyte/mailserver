@extends('statamic::layout')
@section('title', 'Newsletter Groups')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Groups</h1>
        <a href="{{ cp_route('newsletter.groups.create') }}" class="btn-primary">Add Group</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($groups as $group)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold text-lg">{{ $group->name }}</h2>
                        @if($group->description)
                            <p class="text-sm text-gray-500 mt-0.5">{{ $group->description }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ cp_route('newsletter.groups.edit', $group) }}"
                           class="btn-default text-sm">Manage</a>
                        <form method="POST" action="{{ cp_route('newsletter.groups.destroy', $group) }}"
                              onsubmit="return confirm('Delete this group and all its sub-groups?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-default text-sm text-red-500">Delete</button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($group->subGroups as $subGroup)
                        <div class="bg-gray-100 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                            <span>{{ $subGroup->name }}</span>
                            <span class="text-gray-400 text-xs">{{ $subGroup->subscribers_count }} subscribers</span>
                        </div>
                    @endforeach
                    @if($group->subGroups->isEmpty())
                        <span class="text-sm text-gray-400">No sub-groups yet.</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-gray-500">
                No groups found. <a href="{{ cp_route('newsletter.groups.create') }}" class="text-blue">Create one.</a>
            </div>
        @endforelse
    </div>
@endsection
