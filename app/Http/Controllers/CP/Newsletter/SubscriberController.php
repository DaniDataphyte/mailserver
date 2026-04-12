<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscriber::with('subGroups.group')
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sub_group')) {
            $query->whereHas('subGroups', fn ($q) =>
                $q->where('subscriber_sub_groups.id', $request->sub_group)
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) =>
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
            );
        }

        $subscribers = $query->paginate(50)->withQueryString();
        $subGroups   = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.index', compact('subscribers', 'subGroups'));
    }

    public function create()
    {
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.create', compact('subGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email',
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:active,unsubscribed,bounced,complained',
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $subscriber = Subscriber::create([
            'email'      => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name'  => $validated['last_name'] ?? null,
            'status'     => $validated['status'],
        ]);

        $subscriber->subGroups()->attach(
            $validated['sub_groups'],
            ['subscribed_at' => now()]
        );

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber created successfully.');
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load('subGroups.group');

        $sendHistory = $subscriber->campaignSends()
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = $subscriber->sendStats();

        return view('newsletter.cp.subscribers.show', compact('subscriber', 'sendHistory', 'stats'));
    }

    public function edit(Subscriber $subscriber)
    {
        $subscriber->load('subGroups');
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.edit', compact('subscriber', 'subGroups'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'email'      => 'required|email|unique:subscribers,email,' . $subscriber->id,
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'status'     => 'required|in:active,unsubscribed,bounced,complained',
            'sub_groups' => 'required|array|min:1',
            'sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $subscriber->update([
            'email'      => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name'  => $validated['last_name'] ?? null,
            'status'     => $validated['status'],
        ]);

        // Sync sub-groups: detach removed, attach new ones
        $current     = $subscriber->subGroups()->pluck('subscriber_sub_groups.id')->toArray();
        $incoming    = $validated['sub_groups'];
        $toDetach    = array_diff($current, $incoming);
        $toAttach    = array_diff($incoming, $current);

        if ($toDetach) {
            $subscriber->subGroups()->updateExistingPivot($toDetach, ['unsubscribed_at' => now()]);
        }

        if ($toAttach) {
            $subscriber->subGroups()->attach($toAttach, ['subscribed_at' => now()]);
        }

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.show', $subscriber)
            ->with('success', 'Subscriber updated successfully.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('success', 'Subscriber deleted.');
    }
}
