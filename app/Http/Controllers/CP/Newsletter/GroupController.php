<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index()
    {
        $groups = SubscriberGroup::withCount(['subGroups'])
            ->with(['subGroups' => fn ($q) => $q->withCount('subscribers')])
            ->orderBy('name')
            ->get();

        return view('newsletter.cp.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('newsletter.cp.groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:subscriber_groups,name',
            'description' => 'nullable|string',
        ]);

        SubscriberGroup::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group created.');
    }

    public function edit(SubscriberGroup $group)
    {
        $group->load(['subGroups' => fn ($q) => $q->withCount('subscribers')]);

        return view('newsletter.cp.groups.edit', compact('group'));
    }

    public function update(Request $request, SubscriberGroup $group)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:subscriber_groups,name,' . $group->id,
            'description' => 'nullable|string',
        ]);

        $group->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group updated.');
    }

    public function destroy(SubscriberGroup $group)
    {
        $group->delete();

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group deleted.');
    }
}
