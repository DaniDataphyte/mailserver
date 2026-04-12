@extends('statamic::layout')
@section('title', 'Edit Subscriber')

@section('content')
    <div class="mb-6">
        <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← {{ $subscriber->email }}</a>
        <h1 class="text-3xl font-bold">Edit Subscriber</h1>
    </div>

    <form method="POST" action="{{ cp_route('newsletter.subscribers.update', $subscriber) }}" class="max-w-xl">
        @csrf @method('PUT')

        <div class="card p-6 space-y-5">

            <div>
                <label class="publish-field-label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email"
                       value="{{ old('email', $subscriber->email) }}"
                       class="input-text w-full @error('email') border-red-400 @enderror" required>
                @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="publish-field-label">First Name</label>
                    <input type="text" name="first_name"
                           value="{{ old('first_name', $subscriber->first_name) }}"
                           class="input-text w-full">
                </div>
                <div>
                    <label class="publish-field-label">Last Name</label>
                    <input type="text" name="last_name"
                           value="{{ old('last_name', $subscriber->last_name) }}"
                           class="input-text w-full">
                </div>
            </div>

            <div>
                <label class="publish-field-label">Status</label>
                <select name="status" class="input-text w-full">
                    @foreach(['active','unsubscribed','bounced','complained'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $subscriber->status) === $s)>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="publish-field-label">Sub-groups <span class="text-red-500">*</span></label>
                @error('sub_groups') <p class="text-red-500 text-sm mb-1">{{ $message }}</p> @enderror
                @php $activeIds = old('sub_groups', $subscriber->subGroups->pluck('id')->toArray()); @endphp
                <div class="space-y-3 mt-1">
                    @foreach($subGroups->groupBy('group.name') as $groupName => $subs)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                {{ $groupName }}
                            </p>
                            @foreach($subs as $subGroup)
                                <label class="flex items-center gap-2 text-sm cursor-pointer py-0.5">
                                    <input type="checkbox" name="sub_groups[]"
                                           value="{{ $subGroup->id }}"
                                           @checked(in_array($subGroup->id, $activeIds))
                                           class="rounded border-gray-300">
                                    {{ $subGroup->name }}
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}" class="btn-default">Cancel</a>
        </div>
    </form>
@endsection
