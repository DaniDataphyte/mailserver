<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Services\Newsletter\TemplateResolver;
use App\Services\Newsletter\UtmInjector;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

class NewsletterMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Campaign   $campaign,
        public readonly Subscriber $subscriber,
        public readonly string     $campaignSendId,
    ) {}

    /* ------------------------------------------------------------------ */

    public function envelope(): Envelope
    {
        $sender = $this->campaign->sender();

        $replyTo = $sender['reply_to']
            ? [new Address($sender['reply_to'])]
            : [];

        return new Envelope(
            from:    new Address($sender['from_email'], $sender['from_name']),
            replyTo: $replyTo,
            subject: $this->campaign->subject ?? $this->resolveEntry()?->get('subject') ?? $this->campaign->name,
            tags:    ['newsletter', $this->campaign->collection ?? 'general'],
        );
    }

    /* ------------------------------------------------------------------ */

    public function content(): Content
    {
        $entry    = $this->resolveEntry();
        $sender   = $this->campaign->sender();
        $settings = $this->newsletterSettings();

        // Resolve which Blade template to render
        $template = app(TemplateResolver::class)->resolve($entry, $this->campaign->collection);

        // Collection-aware logo and brand colour
        $collection      = $this->campaign->collection ?? '';
        $collectionKey   = str_replace('_newsletters', '', $collection); // insight | foundation
        $collectionLogo  = $this->resolveLogoUrl($settings["{$collectionKey}_logo"] ?? null);
        $headerColor     = $settings["{$collectionKey}_brand_color"]
                            ?? config("newsletter.collections.{$collection}.brand_color", '#1a1a2e');

        // Hero image
        $heroAsset = $entry?->get('hero_image');
        $heroUrl   = $heroAsset ? asset('storage/' . $heroAsset) : null;

        // Inject UTM into bard content, then replace subscriber merge tags
        $rawContent = $entry?->get('content') ?? '';
        $utmParams  = [
            'utm_source'   => 'newsletter',
            'utm_medium'   => 'email',
            'utm_campaign' => 'campaign-' . $this->campaign->id,
        ];
        $content = UtmInjector::inject($rawContent, $utmParams);
        $content = $this->applyMergeTags($content);

        return new Content(
            view: $template,
            with: [
                'subject'            => $this->envelope()->subject,
                'preheader'          => $entry?->get('preheader') ?? '',
                'heroImageUrl'       => $heroUrl,
                'content'            => $content,
                'author'             => $entry?->get('author') ?? $sender['from_name'],
                'fromName'           => $sender['from_name'],
                'sentDate'           => $this->campaign->sent_at?->format('F j, Y') ?? now()->format('F j, Y'),
                'collectionLogo'     => $collectionLogo,
                'headerColor'        => $headerColor,
                'unsubscribeUrl'     => $this->buildSignedUrl('newsletter.unsubscribe.show'),
                'preferencesUrl'     => $this->buildSignedUrl('newsletter.preferences.show'),
                // Subscriber personalisation variables (use in templates directly)
                'subscriberFirstName' => $this->subscriber->first_name ?? '',
                'subscriberLastName'  => $this->subscriber->last_name  ?? '',
                'subscriberFullName'  => $this->subscriber->full_name  ?? $this->subscriber->email,
                'subscriberEmail'     => $this->subscriber->email      ?? '',
            ],
        );
    }

    /* ------------------------------------------------------------------ */

    public function headers(): Headers
    {
        return new Headers(
            messageId:  null,
            references: [],
            text: [
                'List-Unsubscribe'      => '<' . $this->buildSignedUrl('newsletter.unsubscribe.show') . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Campaign-Id'         => (string) $this->campaign->id,
                'X-Campaign-Send-Id'    => $this->campaignSendId,
            ],
        );
    }

    /* ------------------------------------------------------------------ */

    private function resolveEntry(): ?object
    {
        if (! $this->campaign->entry_id) {
            return null;
        }

        return Entry::find($this->campaign->entry_id);
    }

    /**
     * Fetch newsletter_settings GlobalSet data, cached for 1 hour.
     * Falls back to empty array when the GlobalSet hasn't been scaffolded yet.
     */
    private function newsletterSettings(): array
    {
        return cache()->remember('newsletter_settings', 3600, function () {
            $set = GlobalSet::findByHandle('newsletter_settings');

            if (! $set) {
                return [];
            }

            return $set->inDefaultSite()?->data()?->toArray() ?? [];
        });
    }

    /**
     * Convert a Statamic asset value (path string or Asset object) to a
     * fully-qualified public URL.
     */
    private function resolveLogoUrl(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Statamic assets field returns a path string when save_html is used
        if (is_string($value)) {
            return asset('storage/' . ltrim($value, '/'));
        }

        // Asset object (Statamic\Assets\Asset)
        if (is_object($value) && method_exists($value, 'url')) {
            return $value->url();
        }

        return null;
    }

    /**
     * Replace {{merge_tag}} placeholders in the body content with real
     * subscriber data.  Editors type these directly in the Bard field.
     *
     * Supported tags:
     *   {{first_name}}  {{last_name}}  {{full_name}}  {{email}}
     */
    private function applyMergeTags(string $content): string
    {
        $map = [
            '{{first_name}}' => $this->subscriber->first_name ?? '',
            '{{last_name}}'  => $this->subscriber->last_name  ?? '',
            '{{full_name}}'  => $this->subscriber->full_name  ?? $this->subscriber->email,
            '{{email}}'      => $this->subscriber->email      ?? '',
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }

    private function buildSignedUrl(string $routeName): string
    {
        return \URL::signedRoute($routeName, [
            'token' => $this->subscriber->confirmation_token,
        ]);
    }
}
