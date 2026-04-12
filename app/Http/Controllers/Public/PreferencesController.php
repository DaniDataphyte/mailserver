<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    public function show(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This preferences link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)
            ->with('subGroups')
            ->firstOrFail();

        $allSubGroups = SubscriberSubGroup::with('group')
            ->orderBy('subscriber_group_id')
            ->get()
            ->groupBy('group.name');

        $activeSubGroupIds = $subscriber->subGroups->pluck('id')->toArray();

        return view('newsletter.public.preferences', compact(
            'subscriber', 'token', 'allSubGroups', 'activeSubGroupIds'
        ));
    }

    public function update(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This preferences link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)->firstOrFail();

        $request->validate([
            'sub_groups'   => 'nullable|array',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $incoming = $request->input('sub_groups', []);

        // If they unchecked everything — treat as global unsubscribe
        if (empty($incoming)) {
            $subscriber->update([
                'status'          => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
            $subscriber->allSubGroups()->update(['unsubscribed_at' => now()]);

            return view('newsletter.public.unsubscribed', compact('subscriber'));
        }

        $current  = $subscriber->subGroups()->pluck('subscriber_sub_groups.id')->toArray();
        $toRemove = array_diff($current, $incoming);
        $toAdd    = array_diff($incoming, $current);

        if ($toRemove) {
            $subscriber->allSubGroups()->updateExistingPivot($toRemove, [
                'unsubscribed_at' => now(),
            ]);
        }

        if ($toAdd) {
            $subscriber->subGroups()->attach($toAdd, ['subscribed_at' => now()]);
        }

        // Reactivate if they were previously unsubscribed but now selecting groups
        if ($subscriber->status === 'unsubscribed' && ! empty($incoming)) {
            $subscriber->update(['status' => 'active', 'unsubscribed_at' => null]);
        }

        return view('newsletter.public.preferences-saved', compact('subscriber'));
    }
}
