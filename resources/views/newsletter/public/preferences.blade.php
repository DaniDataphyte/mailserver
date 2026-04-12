<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 max-w-md w-full p-8">

        <h1 class="text-xl font-semibold text-gray-900 mb-1">Email Preferences</h1>
        <p class="text-sm text-gray-500 mb-6">
            Managing preferences for <strong>{{ $subscriber->email }}</strong>.
            Select which newsletters you'd like to receive.
        </p>

        <form method="POST"
              action="{{ URL::signedRoute('newsletter.preferences.update', ['token' => $token]) }}"
              x-data="{ selected: {{ json_encode($activeSubGroupIds) }} }">
            @csrf

            <div class="space-y-5">
                @foreach($allSubGroups as $groupName => $subGroups)
                    <div>
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            {{ $groupName }}
                        </h2>
                        <div class="space-y-2">
                            @foreach($subGroups as $subGroup)
                                <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer
                                              transition hover:bg-gray-50"
                                       :class="selected.includes({{ $subGroup->id }})
                                               ? 'border-blue-400 bg-blue-50'
                                               : 'border-gray-200'">
                                    <input type="checkbox"
                                           name="sub_groups[]"
                                           value="{{ $subGroup->id }}"
                                           x-model.number="selected"
                                           class="rounded border-gray-300 text-blue-600 w-4 h-4">
                                    <div>
                                        <span class="font-medium text-sm text-gray-800">{{ $subGroup->name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 space-y-2">
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium
                               py-2.5 px-4 rounded-lg transition text-sm">
                    Save Preferences
                </button>
                <a href="{{ URL::signedRoute('newsletter.unsubscribe.show', ['token' => $token]) }}"
                   class="block text-center text-xs text-gray-400 hover:text-red-500 hover:underline mt-2">
                    Unsubscribe from all
                </a>
            </div>
        </form>
    </div>
</body>
</html>
