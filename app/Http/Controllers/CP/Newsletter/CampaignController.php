<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\DispatchCampaignJob;
use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;

class CampaignController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* Index                                                                */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $query = Campaign::query()->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($collection = $request->input('collection')) {
            $query->where('collection', $collection);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        return view('newsletter.cp.campaigns.index', [
            'campaigns'   => $campaigns,
            'collections' => $this->collectionOptions(),
            'statuses'    => $this->statusOptions(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Create / Store                                                        */
    /* ------------------------------------------------------------------ */

    public function create()
    {
        return view('newsletter.cp.campaigns.create', [
            'collections' => $this->collectionOptions(),
            'subGroups'   => $this->subGroupTree(),
            'entries'     => $this->allEntries(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'collection'   => 'required|in:insight_newsletters,foundation_newsletters',
            'entry_id'     => 'nullable|string',
            'subject'      => 'required|string|max:255',
            'from_name'    => 'nullable|string|max:255',
            'from_email'   => 'nullable|email|max:255',
            'reply_to'     => 'nullable|email|max:255',
            'send_to_all'  => 'nullable|boolean',
            'sub_groups'   => 'nullable|array',
            'sub_groups.*' => 'integer|exists:subscriber_sub_groups,id',
            'action'       => 'required|in:draft,schedule,send',
            'scheduled_at' => 'required_if:action,schedule|nullable|date|after:now',
        ]);

        $status      = match ($data['action']) {
            'schedule' => 'scheduled',
            'send'     => 'sending',
            default    => 'draft',
        };

        $campaign = Campaign::create([
            'name'         => $data['name'],
            'collection'   => $data['collection'],
            'entry_id'     => $data['entry_id'] ?? null,
            'subject'      => $data['subject'],
            'from_name'    => $data['from_name']  ?? null,
            'from_email'   => $data['from_email'] ?? null,
            'reply_to'     => $data['reply_to']   ?? null,
            'status'       => $status,
            'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
            'sent_at'      => $data['action'] === 'send' ? now() : null,
            'created_by'   => auth()->id(),
        ]);

        $this->syncAudiences($campaign, $data);

        if ($data['action'] === 'send') {
            DispatchCampaignJob::dispatch($campaign->id)->onQueue('campaigns');
            return redirect()
                ->route('newsletter.campaigns.show', $campaign)
                ->with('success', 'Campaign is being dispatched.');
        }

        return redirect()
            ->route('newsletter.campaigns.show', $campaign)
            ->with('success', $data['action'] === 'schedule'
                ? 'Campaign scheduled for ' . $campaign->scheduled_at->format('M j, Y g:i A') . '.'
                : 'Campaign saved as draft.'
            );
    }

    /* ------------------------------------------------------------------ */
    /* Show                                                                  */
    /* ------------------------------------------------------------------ */

    public function show(Campaign $campaign)
    {
        $campaign->load('audiences.targetable', 'sends');

        $stats = $campaign->stats();

        $entry = $campaign->entry_id
            ? Entry::find($campaign->entry_id)
            : null;

        $recentSends = $campaign->sends()
            ->with('subscriber')
            ->latest('sent_at')
            ->limit(20)
            ->get();

        return view('newsletter.cp.campaigns.show', compact('campaign', 'stats', 'entry', 'recentSends'));
    }

    /* ------------------------------------------------------------------ */
    /* Edit / Update                                                         */
    /* ------------------------------------------------------------------ */

    public function edit(Campaign $campaign)
    {
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be edited.');

        $campaign->load('audiences');

        $selectedSubGroupIds = $campaign->audiences
            ->where('targetable_type', 'subscriber_sub_group')
            ->pluck('targetable_id')
            ->toArray();

        $sendToAll = $campaign->audiences
            ->where('targetable_type', 'subscriber_group')
            ->isNotEmpty();

        return view('newsletter.cp.campaigns.edit', [
            'campaign'            => $campaign,
            'collections'         => $this->collectionOptions(),
            'subGroups'           => $this->subGroupTree(),
            'entries'             => $this->allEntries(),
            'selectedSubGroupIds' => $selectedSubGroupIds,
            'sendToAll'           => $sendToAll,
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be edited.');

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'collection'   => 'required|in:insight_newsletters,foundation_newsletters',
            'entry_id'     => 'nullable|string',
            'subject'      => 'required|string|max:255',
            'from_name'    => 'nullable|string|max:255',
            'from_email'   => 'nullable|email|max:255',
            'reply_to'     => 'nullable|email|max:255',
            'send_to_all'  => 'nullable|boolean',
            'sub_groups'   => 'nullable|array',
            'sub_groups.*' => 'integer|exists:subscriber_sub_groups,id',
            'action'       => 'required|in:draft,schedule,send',
            'scheduled_at' => 'required_if:action,schedule|nullable|date|after:now',
        ]);

        $status = match ($data['action']) {
            'schedule' => 'scheduled',
            'send'     => 'sending',
            default    => 'draft',
        };

        $campaign->update([
            'name'         => $data['name'],
            'collection'   => $data['collection'],
            'entry_id'     => $data['entry_id'] ?? null,
            'subject'      => $data['subject'],
            'from_name'    => $data['from_name']  ?? null,
            'from_email'   => $data['from_email'] ?? null,
            'reply_to'     => $data['reply_to']   ?? null,
            'status'       => $status,
            'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
            'sent_at'      => $data['action'] === 'send' ? now() : null,
        ]);

        $this->syncAudiences($campaign, $data);

        if ($data['action'] === 'send') {
            DispatchCampaignJob::dispatch($campaign->id)->onQueue('campaigns');
            return redirect()
                ->route('newsletter.campaigns.show', $campaign)
                ->with('success', 'Campaign is being dispatched.');
        }

        return redirect()
            ->route('newsletter.campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    /* ------------------------------------------------------------------ */
    /* Destroy                                                              */
    /* ------------------------------------------------------------------ */

    public function destroy(Campaign $campaign)
    {
        abort_if(
            in_array($campaign->status, ['sending', 'sent']),
            403,
            'Cannot delete a campaign that has been sent or is currently sending.'
        );

        $campaign->delete();

        return redirect()
            ->route('newsletter.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Cancel Scheduled                                                     */
    /* ------------------------------------------------------------------ */

    public function cancel(Campaign $campaign)
    {
        abort_if($campaign->status !== 'scheduled', 403, 'Only scheduled campaigns can be cancelled.');

        $campaign->update(['status' => 'draft', 'scheduled_at' => null]);

        return redirect()
            ->route('newsletter.campaigns.show', $campaign)
            ->with('success', 'Campaign moved back to draft.');
    }

    /* ------------------------------------------------------------------ */
    /* Send Now (from show/draft)                                           */
    /* ------------------------------------------------------------------ */

    public function send(Campaign $campaign)
    {
        abort_if(
            ! in_array($campaign->status, ['draft', 'scheduled']),
            403,
            'Campaign cannot be sent in its current state.'
        );

        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        DispatchCampaignJob::dispatch($campaign->id)->onQueue('campaigns');

        return redirect()
            ->route('newsletter.campaigns.show', $campaign)
            ->with('success', 'Campaign is being dispatched to the queue.');
    }

    /* ------------------------------------------------------------------ */
    /* Test Send                                                            */
    /* ------------------------------------------------------------------ */

    public function testSend(Request $request, Campaign $campaign)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');

        // Use an existing subscriber or a synthetic one
        $subscriber = \App\Models\Subscriber::where('email', $email)->first()
            ?? $this->syntheticSubscriber($email);

        try {
            Mail::to($email)->send(
                new NewsletterMailable($campaign, $subscriber, 'test-' . time())
            );

            return redirect()
                ->route('newsletter.campaigns.show', $campaign)
                ->with('success', "Test email sent to {$email}.");

        } catch (\Throwable $e) {
            return redirect()
                ->route('newsletter.campaigns.show', $campaign)
                ->with('error', "Test send failed: {$e->getMessage()}");
        }
    }

    private function syntheticSubscriber(string $email): \App\Models\Subscriber
    {
        $s = new \App\Models\Subscriber([
            'email'              => $email,
            'first_name'         => 'Test',
            'last_name'          => 'Recipient',
            'status'             => 'active',
            'confirmation_token' => Str::uuid()->toString(),
        ]);
        $s->id = 0;
        return $s;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    private function syncAudiences(Campaign $campaign, array $data): void
    {
        // Remove all existing audience rows for this campaign
        $campaign->audiences()->delete();

        if (! empty($data['send_to_all'])) {
            // Resolve the group from the collection handle
            $groupSlug = match ($data['collection']) {
                'insight_newsletters'    => 'insight-subscribers',
                'foundation_newsletters' => 'foundation',
                default                  => null,
            };

            $group = $groupSlug
                ? SubscriberGroup::where('slug', $groupSlug)->first()
                : null;

            if ($group) {
                CampaignAudience::create([
                    'campaign_id'      => $campaign->id,
                    'targetable_type'  => 'subscriber_group',
                    'targetable_id'    => $group->id,
                    'send_to_all'      => true,
                ]);
            }
            return;
        }

        foreach ($data['sub_groups'] ?? [] as $subGroupId) {
            CampaignAudience::create([
                'campaign_id'     => $campaign->id,
                'targetable_type' => 'subscriber_sub_group',
                'targetable_id'   => $subGroupId,
                'send_to_all'     => false,
            ]);
        }
    }

    private function collectionOptions(): array
    {
        return [
            'insight_newsletters'    => 'Dataphyte Insight',
            'foundation_newsletters' => 'Dataphyte Foundation',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft'    => 'Draft',
            'scheduled' => 'Scheduled',
            'sending'  => 'Sending',
            'sent'     => 'Sent',
            'failed'   => 'Failed',
        ];
    }

    private function subGroupTree(): \Illuminate\Support\Collection
    {
        return SubscriberGroup::with('subGroups')->get();
    }

    private function allEntries(): array
    {
        $entries = [];

        foreach (['insight_newsletters', 'foundation_newsletters'] as $collection) {
            $collectionEntries = Entry::query()
                ->where('collection', $collection)
                ->orderBy('date', 'desc')
                ->get();

            foreach ($collectionEntries as $entry) {
                $entries[$collection][] = [
                    'id'      => $entry->id(),
                    'title'   => $entry->get('title') ?: $entry->get('subject') ?: '(Untitled)',
                    'subject' => $entry->get('subject') ?? '',
                    'date'    => optional($entry->date())->format('M j, Y') ?? '',
                ];
            }
        }

        return $entries;
    }
}
