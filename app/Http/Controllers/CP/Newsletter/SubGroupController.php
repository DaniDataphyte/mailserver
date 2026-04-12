<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubGroupController extends Controller
{
    public function store(Request $request, SubscriberGroup $group)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->subGroups()->create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group added.');
    }

    public function update(Request $request, SubscriberGroup $group, SubscriberSubGroup $subGroup)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subGroup->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group updated.');
    }

    public function destroy(SubscriberGroup $group, SubscriberSubGroup $subGroup)
    {
        $subGroup->delete();

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group deleted.');
    }
}
