<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\DispatchCampaignJob;
use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\TemplateResolver;
use App\Services\Newsletter\UtmInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

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
            'from_name'    => $this->blankToNull($data['from_name']  ?? null),
            'from_email'   => $this->blankToNull($data['from_email'] ?? null),
            'reply_to'     => $this->blankToNull($data['reply_to']   ?? null),
            'status'       => $status,
            'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
            'sent_at'      => $data['action'] === 'send' ? now() : null,
            'created_by'   => auth()->id(),
        ]);

        $this->syncAudiences($campaign, $data);

        if ($data['action'] === 'send') {
            DispatchCampaignJob::dispatch($campaign->id)->onQueue('campaigns');
            return redirect(cp_route('newsletter.campaigns.show', $campaign))
                ->with('success', 'Campaign is being dispatched.');
        }

        return redirect(cp_route('newsletter.campaigns.show', $campaign))
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
            'from_name'    => $this->blankToNull($data['from_name']  ?? null),
            'from_email'   => $this->blankToNull($data['from_email'] ?? null),
            'reply_to'     => $this->blankToNull($data['reply_to']   ?? null),
            'status'       => $status,
            'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
            'sent_at'      => $data['action'] === 'send' ? now() : null,
        ]);

        $this->syncAudiences($campaign, $data);

        if ($data['action'] === 'send') {
            DispatchCampaignJob::dispatch($campaign->id)->onQueue('campaigns');
            return redirect(cp_route('newsletter.campaigns.show', $campaign))
                ->with('success', 'Campaign is being dispatched.');
        }

        return redirect(cp_route('newsletter.campaigns.show', $campaign))
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

        return redirect(cp_route('newsletter.campaigns.index'))
            ->with('success', 'Campaign deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Cancel Scheduled                                                     */
    /* ------------------------------------------------------------------ */

    public function cancel(Campaign $campaign)
    {
        abort_if($campaign->status !== 'scheduled', 403, 'Only scheduled campaigns can be cancelled.');

        $campaign->update(['status' => 'draft', 'scheduled_at' => null]);

        return redirect(cp_route('newsletter.campaigns.show', $campaign))
            ->with('success', 'Campaign moved back to draft.');
    }

    /* ------------------------------------------------------------------ */
    /* Reset Stuck Campaign to Draft                                       */
    /* ------------------------------------------------------------------ */

    public function resetToDraft(Campaign $campaign)
    {
        abort_if(
            ! in_array($campaign->status, ['sending', 'failed']),
            403,
            'Only campaigns stuck in sending or failed state can be reset.'
        );

        $campaign->update(['status' => 'draft', 'sent_at' => null]);

        return redirect(cp_route('newsletter.campaigns.show', $campaign))
            ->with('success', 'Campaign reset to draft — you can send it again.');
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

        return redirect(cp_route('newsletter.campaigns.show', $campaign))
            ->with('success', 'Campaign is being dispatched to the queue.');
    }

    /* ------------------------------------------------------------------ */
    /* Browser Preview                                                     */
    /* ------------------------------------------------------------------ */

    public function preview(Campaign $campaign)
    {
        $entry    = $campaign->entry_id ? Entry::find($campaign->entry_id) : null;
        $settings = $this->campaignNewsletterSettings();
        $sender   = $campaign->sender();

        $collection      = $campaign->collection ?? '';
        $collectionKey   = str_replace('_newsletters', '', $collection);
        $collectionLogo  = $this->campaignAssetUrl($settings["{$collectionKey}_logo"] ?? null);
        $headerColor     = $settings["{$collectionKey}_brand_color"]
                            ?? config("newsletter.collections.{$collection}.brand_color", '#1a1a2e');

        $template = app(TemplateResolver::class)->resolve($entry, $collection);

        $heroUrl    = $this->campaignAssetUrl($entry?->get('hero_image'));

        $rawContent = $entry?->get('content') ?? '<p><em>(No content yet — link an entry to this campaign.)</em></p>';
        $content    = UtmInjector::inject($rawContent, [
            'utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'preview',
        ]);

        // Replace merge tags with visible placeholders for the preview
        $content = str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}', '{{email}}'],
            ['[First Name]',   '[Last Name]',   '[Full Name]',   '[email@example.com]'],
            $content
        );

        $html = view($template, [
            'subject'             => $campaign->subject ?? '(No subject)',
            'preheader'           => $entry?->get('preheader') ?? '',
            'heroImageUrl'        => $heroUrl,
            'content'             => $content,
            'author'              => $entry?->get('author') ?? $sender['from_name'],
            'fromName'            => $sender['from_name'],
            'sentDate'            => now()->format('F j, Y'),
            'collectionLogo'      => $collectionLogo,
            'headerColor'         => $headerColor,
            'unsubscribeUrl'      => '#',
            'preferencesUrl'      => '#',
            'subscriberFirstName' => '[First Name]',
            'subscriberLastName'  => '[Last Name]',
            'subscriberFullName'  => '[Full Name]',
            'subscriberEmail'     => '[email@example.com]',
        ])->render();

        $banner = '<div style="background:#1a73e8;color:#fff;padding:10px 20px;'
            . 'font-family:system-ui,sans-serif;font-size:13px;display:flex;'
            . 'align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9999;">'
            . '<span>👁 Preview: <strong>' . e($campaign->name) . '</strong>'
            . ' &nbsp;·&nbsp; Template: <code style="background:rgba(255,255,255,.2);'
            . 'padding:1px 5px;border-radius:3px;">' . e($template) . '</code></span>'
            . '<span style="opacity:.8;font-size:12px;">Links disabled in preview</span>'
            . '</div>';

        return response($banner . $html)->header('Content-Type', 'text/html');
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

            return redirect(cp_route('newsletter.campaigns.show', $campaign))
                ->with('success', "Test email sent to {$email}.");

        } catch (\Throwable $e) {
            return redirect(cp_route('newsletter.campaigns.show', $campaign))
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
                    'id'        => $entry->id(),
                    'title'     => $entry->get('title') ?: $entry->get('subject') ?: '(Untitled)',
                    'subject'   => $entry->get('subject') ?? '',
                    'date'      => optional($entry->date())->format('M j, Y') ?? '',
                    'blueprint' => $entry->blueprint()?->title() ?? $entry->blueprint()?->handle() ?? '',
                ];
            }
        }

        return $entries;
    }

    /** Convert empty string to null so optional sender fields don't trip NOT NULL (now nullable). */
    private function blankToNull(?string $value): ?string
    {
        return ($value === null || trim($value) === '') ? null : $value;
    }

    /** Fetch newsletter_settings GlobalSet, cached 1 hour (mirrors Mailable). */
    private function campaignNewsletterSettings(): array
    {
        return cache()->remember('newsletter_settings', 3600, function () {
            $set = GlobalSet::findByHandle('newsletter_settings');
            return $set ? ($set->inDefaultSite()?->data()?->toArray() ?? []) : [];
        });
    }

    /** Convert a Statamic asset path/object to a public URL (mirrors Mailable). */
    private function campaignAssetUrl(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_array($value)) {
            return $this->campaignAssetUrl(reset($value) ?: null);
        }

        if (is_object($value) && method_exists($value, 'value') && ! method_exists($value, 'url')) {
            return $this->campaignAssetUrl($value->value());
        }

        if (is_object($value) && method_exists($value, 'url')) {
            return $this->normalizeAssetUrl($value->url());
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '//')) {
                return $this->normalizeAssetUrl($value);
            }

            if (str_starts_with($value, '/')) {
                return $this->normalizeAssetUrl(url($value, [], $this->shouldUseHttpsForAssets()));
            }

            return $this->normalizeAssetUrl(asset('storage/' . ltrim($value, '/'), $this->shouldUseHttpsForAssets()));
        }

        return null;
    }

    private function normalizeAssetUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return ($this->shouldUseHttpsForAssets() ? 'https:' : 'http:') . $url;
        }

        if ($this->shouldUseHttpsForAssets() && str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return url($url, [], $this->shouldUseHttpsForAssets());
        }

        return $url;
    }

    private function shouldUseHttpsForAssets(): bool
    {
        if (app()->bound('request')) {
            try {
                return request()->isSecure();
            } catch (\Throwable) {
                // Fall through to config-based detection when there is no active request.
            }
        }

        $assetRoot = config('app.asset_url') ?: config('app.url');

        return parse_url((string) $assetRoot, PHP_URL_SCHEME) === 'https';
    }
}
