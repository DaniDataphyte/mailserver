@extends('statamic::layout')
@section('title', 'Import Subscribers')

@section('content')
    <div class="mb-6">
        <a href="{{ cp_route('newsletter.subscribers.index') }}"
           class="text-sm text-gray-500 hover:underline mb-1 block">← Subscribers</a>
        <h1 class="text-3xl font-bold">Import Subscribers</h1>
    </div>

    <div class="max-w-2xl space-y-6">

        {{-- Format guide --}}
        <div class="card p-5">
            <h2 class="font-semibold mb-2">CSV Format</h2>
            <p class="text-sm text-gray-600 mb-3">
                Your CSV must have an <code class="bg-gray-100 px-1 rounded">email</code> column.
                All other columns are optional. The <code class="bg-gray-100 px-1 rounded">sub_groups</code>
                column accepts comma-separated sub-group slugs.
            </p>
            <pre class="bg-gray-50 border rounded p-3 text-xs overflow-x-auto">email,first_name,last_name,sub_groups
john@example.com,John,Doe,"topics,senorrita"
jane@example.com,Jane,Smith,weekly
alex@example.com,,,activities</pre>

            <p class="text-sm text-gray-600 mt-3">Available sub-group slugs:</p>
            <ul class="text-xs text-gray-500 mt-1 columns-2 gap-4">
                @foreach($subGroups as $sg)
                    <li><code class="bg-gray-100 px-1 rounded">{{ $sg->slug }}</code> — {{ $sg->group->name }}: {{ $sg->name }}</li>
                @endforeach
            </ul>
        </div>

        {{-- Import form --}}
        <form method="POST"
              action="{{ cp_route('newsletter.subscribers.import') }}"
              enctype="multipart/form-data"
              class="card p-5 space-y-5">
            @csrf

            <div>
                <label class="publish-field-label">CSV File <span class="text-red-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt"
                       class="block w-full text-sm text-gray-600
                              file:mr-4 file:py-2 file:px-4 file:rounded
                              file:border-0 file:text-sm file:font-medium
                              file:bg-blue file:text-white
                              hover:file:bg-blue-dark cursor-pointer">
                @error('csv_file') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="publish-field-label">
                    Default Sub-groups
                    <span class="text-gray-400 font-normal">(applied to all rows that have no sub_groups column)</span>
                </label>
                <div class="space-y-3 mt-1">
                    @foreach($subGroups->groupBy('group.name') as $groupName => $subs)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                {{ $groupName }}
                            </p>
                            @foreach($subs as $subGroup)
                                <label class="flex items-center gap-2 text-sm cursor-pointer py-0.5">
                                    <input type="checkbox" name="default_sub_groups[]"
                                           value="{{ $subGroup->id }}"
                                           class="rounded border-gray-300">
                                    {{ $subGroup->name }}
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Import</button>
            </div>
        </form>
    </div>
@endsection
