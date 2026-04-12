<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 max-w-md w-full p-8 text-center">

        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900 mb-2">You've been unsubscribed</h1>
        <p class="text-gray-500 text-sm">
            <strong>{{ $subscriber->email }}</strong> has been removed from all mailing lists.
            You will not receive any further emails from us.
        </p>

        <p class="text-xs text-gray-400 mt-6">
            Unsubscribed by mistake?
            <a href="{{ URL::signedRoute('newsletter.preferences.show', ['token' => $subscriber->confirmation_token]) }}"
               class="text-blue-500 hover:underline">Manage your preferences</a>
        </p>
    </div>
</body>
</html>
