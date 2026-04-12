<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 max-w-md w-full p-8 text-center">

        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900 mb-2">Unsubscribe</h1>
        <p class="text-gray-500 text-sm mb-6">
            You are about to unsubscribe <strong>{{ $subscriber->email }}</strong>
            from all newsletters. You will not receive any further emails from us.
        </p>

        <form method="POST" action="{{ URL::signedRoute('newsletter.unsubscribe.process', ['token' => $token]) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 px-4 rounded-lg transition">
                Yes, unsubscribe me
            </button>
        </form>

        <a href="{{ URL::signedRoute('newsletter.preferences.show', ['token' => $token]) }}"
           class="block mt-3 text-sm text-blue-600 hover:underline">
            Manage my preferences instead
        </a>
    </div>
</body>
</html>
