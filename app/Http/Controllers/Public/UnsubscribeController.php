<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This unsubscribe link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)->firstOrFail();

        return view('newsletter.public.unsubscribe', compact('subscriber', 'token'));
    }

    public function process(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This unsubscribe link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)->firstOrFail();

        $subscriber->update([
            'status'          => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        // Mark all sub-group pivots as unsubscribed
        $subscriber->allSubGroups()->update(['unsubscribed_at' => now()]);

        return view('newsletter.public.unsubscribed', compact('subscriber'));
    }
}
