# Statamic Content Architecture

How newsletter content maps to Statamic's native structures.

---

## Collections (one per subscriber group)

Each subscriber group has its own collection. Each newsletter edition = one entry.

| Collection handle | Label | Audience |
|---|---|---|
| `insight_newsletters` | Insight Newsletters | Insight Subscribers |
| `foundation_newsletters` | Foundation Newsletters | Foundation |

Separate collections because:
- Different blueprints (different fields per newsletter type)
- Independent CP permissions per editorial team
- Clean "view in browser" URLs (`/newsletters/insight/{slug}`, `/newsletters/foundation/{slug}`)
- Separate entry lists in the CP sidebar

New newsletter groups in the future = new collection.

---

## Taxonomy: Newsletter Audiences

Sub-groups become taxonomy terms, not hardcoded values. Editors pick audience terms when creating an entry. New sub-groups = add a new term in the CP, no code changes.

```
Taxonomy handle: newsletter_audiences

Terms (starting set):
  Insight group:
    - topics
    - marina-maitama
    - senorrita

  Foundation group:
    - weekly
    - activities
```

Each entry can target one or more terms (send to Topics only, or Topics + SenorRita, or all Insight Subscribers).

A special "All [Group Name]" option on the entry triggers a send to every subscriber in the parent group, ignoring sub-group targeting.

---

## Blueprint Fields (per collection)

### insight_newsletters blueprint
```
subject          (text)           - Email subject line
from_name        (text)           - Sender display name
from_email       (text)           - Sender email
reply_to         (text, optional)
template         (select)         - Choose from email_templates
content          (bard/richtext)  - Newsletter body
audiences        (terms)          - Taxonomy: newsletter_audiences (filtered to Insight terms)
send_to_all      (toggle)         - Override: send to all Insight Subscribers
scheduled_at     (date/time)      - Leave blank for manual send
```

### foundation_newsletters blueprint
Same structure, `audiences` field filtered to Foundation taxonomy terms.

---

## How Entries Link to the Campaign Table

The `campaigns` MySQL table has an `entry_id` column (Statamic entry UUID) and a `collection` column.

```
Statamic Entry (content, blueprint, URL)
  └── campaigns row
        ├── entry_id      → Statamic entry UUID
        ├── collection    → 'insight_newsletters' | 'foundation_newsletters'
        ├── status        → draft | scheduled | sending | sent | cancelled
        ├── scheduled_at
        ├── sent_at
        ├── total_recipients
        └── campaign_sends rows (one per subscriber)
```

The entry owns the content and the "view in browser" URL.
The campaigns row owns the send state and tracking.

---

## View in Browser

Since entries have their own URLs in Statamic, the "view in browser" link in each email is simply the entry's public URL, rendered with the Antlers/Blade email template.

```
https://yourdomain.com/newsletters/insight/edition-slug
https://yourdomain.com/newsletters/foundation/weekly-update-april
```

No separate web version route needed — Statamic handles it.

---

## CP Workflow for Sending a Newsletter

1. Editor creates a new entry in `insight_newsletters` or `foundation_newsletters`
2. Fills in subject, content, selects audience terms (or toggles "send to all")
3. Optionally sets `scheduled_at`
4. Publishes the entry → triggers creation of a `campaigns` row with status `draft`
5. Admin reviews campaign in the Newsletter CP section (sees recipient count preview)
6. Admin clicks "Send" (or campaign sends at scheduled time)
7. System dispatches `DispatchCampaignJob`

---

## Antlers vs Blade for Email Templates

**Entry editing in CP** → Antlers (standard Statamic, handled automatically)
**Rendered email HTML sent to subscribers** → Blade (Laravel Mailable renders Blade)
**Blueprint field content** → Statamic augments fields to HTML before Blade receives them

They work together — Blade handles the template structure, Statamic handles the content rendering.

### Template Selection in Blueprint

Each collection Blueprint has an `email_template` select field. The editor picks the
layout when writing the entry. The Mailable maps the selected value directly to a Blade file.

```yaml
# In Blueprint YAML
-
  handle: email_template
  field:
    type: select
    display: Email Template
    options:
      emails/insight/feature-lead: Feature Lead (large hero image)
      emails/insight/digest: Digest (multiple short items)
      emails/insight/single-story: Single Story
    required: true
```

### Blueprint Fields → Blade Variables

Statamic augments Blueprint fields automatically. Bard fields return HTML, Asset fields
return URLs — pass them straight into Blade:

```php
// In the Mailable class
$entry = Entry::find($campaign->entry_id);

return $this->view($entry->email_template)
    ->with([
        'subject'        => $entry->subject,
        'content'        => $entry->content->toHtml(),  // Bard → HTML
        'hero_image'     => $entry->hero_image?->url(), // Asset → URL
        'author'         => $entry->author,
        'preheader'      => $entry->preheader,
        'subscriber'     => $this->subscriber,
        'unsubscribeUrl' => $this->unsubscribeUrl,
        'preferencesUrl' => $this->preferencesUrl,
    ]);
```

### Blade Template Structure

```
resources/views/emails/
├── layouts/
│   └── base.blade.php           ← shared: DOCTYPE, head, footer, unsubscribe link
├── insight/
│   ├── feature-lead.blade.php   ← hero image + headline + body
│   ├── digest.blade.php         ← multiple short story items
│   └── single-story.blade.php  ← clean single article layout
└── foundation/
    ├── weekly.blade.php
    └── activities.blade.php
```

Each template extends base and slots in the Blueprint variables:

```blade
{{-- emails/insight/feature-lead.blade.php --}}
@extends('emails.layouts.base')

@section('content')
    @if($hero_image)
        <img src="{{ $hero_image }}" alt="">
    @endif
    <h1>{{ $subject }}</h1>
    <div class="author">{{ $author }}</div>
    {!! $content !!}   {{-- Pre-rendered HTML from Bard --}}
@endsection
```

### Blueprint Fields Reference (per collection)

| Field handle | Type | Purpose |
|---|---|---|
| `subject` | Text | Email subject line and h1 |
| `email_template` | Select | Which Blade template to render |
| `content` | Bard | Newsletter body (augments to HTML) |
| `hero_image` | Assets | Optional header image |
| `author` | Text | Byline |
| `preheader` | Text | Inbox preview text (hidden in email body) |
| `reply_to` | Text | Optional per-campaign reply address |
| `audiences` | Taxonomy | Which sub-groups to target |
| `send_to_all` | Toggle | Override: send to entire parent group |
| `scheduled_at` | Date/Time | Schedule the send |

---

## Adding a New Collection (Checklist)

Do these five things in order whenever a new newsletter collection is created:

### 1. `.env` — sender identity
```
NEWSLETTER_[NAME]_FROM_EMAIL=newsletter@newdomain.com
NEWSLETTER_[NAME]_FROM_NAME="New Collection Name"
NEWSLETTER_[NAME]_REPLY_TO=
```

### 2. `config/newsletter.php` — register the collection
```php
'collection_handle' => [
    'from_email' => env('NEWSLETTER_[NAME]_FROM_EMAIL'),
    'from_name'  => env('NEWSLETTER_[NAME]_FROM_NAME'),
    'reply_to'   => env('NEWSLETTER_[NAME]_REPLY_TO', ''),
],
```
Handle must match the Statamic collection handle exactly.

### 3. Statamic CP — collection + blueprint
- Create Collection with the matching handle
- Create Blueprint (subject, content, email_template, audiences, send_to_all, scheduled_at)
- Add audience terms to the `newsletter_audiences` taxonomy

### 4. Database — subscriber group
- Create a `subscriber_group` row in the CP
- Add sub-groups under it as needed

### 5. Email templates — Blade files
- Create `resources/views/emails/{collection}/` directory
- Add at least one layout Blade file
- Insert a row into `email_templates` table so the Blueprint select field shows it

### What does NOT need changing
Migrations, models, queues, Horizon, ElasticEmailTransport, webhook handler — all collection-agnostic.

### Extra step if using a new sending domain
- Add SPF, DKIM, DMARC records for the new domain at your DNS provider
- Verify the new domain in Elastic Email (Settings > Domains)
