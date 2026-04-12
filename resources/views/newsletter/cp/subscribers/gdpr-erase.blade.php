@extends('statamic::layout')
@section('title', 'Erase Subscriber Data')

@section('content')
<div class="max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
           class="text-grey-60 hover:text-grey-80 text-sm">&larr; {{ $subscriber->full_name }}</a>
        <h1 class="text-3xl font-bold text-red">Erase Subscriber Data</h1>
    </div>

    <div class="card p-6 border-red-300 mb-6">
        <div class="flex gap-3 mb-4">
            <svg class="w-6 h-6 text-red flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h2 class="font-semibold text-red mb-1">This action is permanent and irreversible</h2>
                <p class="text-sm text-grey-70">
                    Erasing this subscriber will permanently anonymise all personally identifiable
                    information in compliance with GDPR Article 17 (Right to Erasure).
                </p>
            </div>
        </div>

        <div class="bg-grey-10 rounded p-4 mb-4 text-sm space-y-1">
            <p class="font-semibold text-grey-80 mb-2">The following will be anonymised:</p>
            <p class="text-grey-70">✓ Email address → <code class="text-xs bg-grey-20 px-1 rounded">deleted-{{ $subscriber->id }}@deleted.invalid</code></p>
            <p class="text-grey-70">✓ First name, last name</p>
            <p class="text-grey-70">✓ IP address, user agent, metadata</p>
            <p class="text-grey-70">✓ Confirmation token</p>
            <p class="text-grey-70">✓ All sub-group memberships removed</p>
            <p class="text-grey-60 mt-2">Campaign send records (delivery/open stats) are retained anonymously for statistical integrity.</p>
        </div>

        <div class="bg-blue-lighter rounded p-3 text-xs text-blue-dark">
            <strong>Tip:</strong> If the subscriber only wants to stop receiving emails,
            use <a href="{{ cp_route('newsletter.subscribers.edit', $subscriber) }}" class="underline">Unsubscribe</a> instead.
        </div>
    </div>

    <div class="card p-6">
        <p class="text-sm font-medium text-grey-80 mb-4">
            Subscriber: <strong>{{ $subscriber->full_name }}</strong>
            <span class="text-grey-50">({{ $subscriber->email }})</span>
        </p>

        <form method="POST"
              action="{{ cp_route('newsletter.subscribers.gdpr.erase', $subscriber) }}">
            @csrf
            @method('DELETE')

            <div class="mb-4">
                <label class="block text-sm font-medium text-grey-80 mb-1">
                    Type <strong>ERASE</strong> to confirm
                </label>
                <input type="text" name="confirm"
                       class="input-text w-full @error('confirm') border-red @enderror"
                       placeholder="ERASE"
                       autocomplete="off">
                @error('confirm')
                    <p class="text-red text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="btn bg-red border-red text-white hover:bg-red-dark">
                    Permanently Erase Data
                </button>
                <a href="{{ cp_route('newsletter.subscribers.show', $subscriber) }}"
                   class="btn">Cancel</a>
            </div>
        </form>
    </div>

</div>
@endsection
