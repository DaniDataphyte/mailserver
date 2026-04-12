# Adding a New Newsletter Collection

Complete reference for adding a new newsletter collection — a new brand/product
with its own sender identity, subscriber group, audiences, blueprints and email templates.

Follow steps in order. Nothing outside this checklist needs to change.

---

## Architecture Overview

```
New Collection
├── Statamic collection       → where editors write entries (one entry = one newsletter issue)
├── Multiple blueprints       → one per email format/product (each carries a hidden template field)
├── Taxonomy terms            → the audience sub-groups editors can target in each entry
├── Subscriber group          → DB group that maps to this collection's subscribers
├── Sub-groups                → granular segments (one per taxonomy term)
├── Blade email templates     → one .blade.php per blueprint
├── GlobalSet fields          → logo + brand colour uploaded via CP
└── config/newsletter.php     → sender identity (from address/name)
```

Current collections:

| Handle | Sender Name | From Address |
|---|---|---|
| `insight_newsletters` | Dataphyte Insight | newsletter@dataphyte.com |
| `foundation_newsletters` | Dataphyte Foundation | newsletter@dataphyte.org |

---

## Step 1 — Add Sender Identity to `.env`

```dotenv
NEWSLETTER_CULTURE_FROM_EMAIL=newsletter@dataphyte.com
NEWSLETTER_CULTURE_FROM_NAME="Dataphyte Culture"
NEWSLETTER_CULTURE_REPLY_TO=
```

> `REPLY_TO` can be blank — replies go to the from address.

---

## Step 2 — Register in `config/newsletter.php`

The array key **must exactly match** the Statamic collection handle you create in Step 3.

```php
'collections' => [

    // ... existing collections ...

    'culture_newsletters' => [
        'from_email'  => env('NEWSLETTER_CULTURE_FROM_EMAIL', 'newsletter@dataphyte.com'),
        'from_name'   => env('NEWSLETTER_CULTURE_FROM_NAME', 'Dataphyte Culture'),
        'reply_to'    => env('NEWSLETTER_CULTURE_REPLY_TO', ''),
        'brand_color' => '#7c3aed',  // header background for emails
    ],

],
```

Then clear config cache:
```bash
php artisan config:clear
```

---

## Step 3 — Add to `ScaffoldCollections` Command

Open `app/Console/Commands/Newsletter/ScaffoldCollections.php`.

### 3a — Add blueprintDefinitions()

```php
'culture_newsletters' => [
    ['handle' => 'weekly',      'title' => 'Weekly Update',  'template' => 'emails.culture.weekly'],
    ['handle' => 'spotlight',   'title' => 'Spotlight',      'template' => 'emails.culture.spotlight'],
    // add more as needed
],
```

Each entry:
- `handle` — snake_case identifier, becomes blueprint handle and part of convention fallback
- `title`  — shown in CP when editors choose which blueprint to use
- `template` — the Blade view key; this is auto-filled into the hidden `template` field on every entry

### 3b — Add `scaffoldCollection()` call in `handle()`

```php
$this->scaffoldCollection(
    'culture_newsletters',
    'Culture Newsletters',
    '/newsletters/culture/{slug}',
);
```

### 3c — Add GlobalSet fields

In `scaffoldNewsletterSettings()`, add a new section inside the blueprint contents:

```php
'culture' => [
    'display' => 'Culture Newsletter',
    'fields'  => [
        [
            'handle' => 'culture_logo',
            'field'  => [
                'type'          => 'assets',
                'display'       => 'Culture Collection Logo',
                'instructions'  => 'Appears in the header of every Culture newsletter email.',
                'container'     => 'assets',
                'max_files'     => 1,
                'allow_uploads' => true,
                'restrict'      => false,
                'width'         => 50,
            ],
        ],
        [
            'handle' => 'culture_brand_color',
            'field'  => [
                'type'    => 'color',
                'display' => 'Culture Brand Color',
                'default' => '#7c3aed',
                'width'   => 50,
            ],
        ],
    ],
],
```

Also seed the default brand color in `scaffoldNewsletterSettings()`:
```php
$variables->data([
    // ... existing defaults ...
    'culture_brand_color' => '#7c3aed',
]);
```

### 3d — Run the scaffold

```bash
php artisan newsletter:scaffold
```

This creates the collection, all blueprints (each with a hidden `template` field), and
updates the GlobalSet blueprint — without touching existing collections.

---

## Step 4 — Create the Subscriber Group

Via CP: **Newsletter → Groups → Create Group**

| Field | Value |
|---|---|
| Name | Culture |
| Slug | `culture` |

Then add sub-groups under it. Each sub-group must have a slug that matches the
taxonomy term you will create in Step 5.

| Sub-group Name | Sub-group Slug |
|---|---|
| Arts | `arts` |
| Music | `music` |

---

## Step 5 — Add Taxonomy Terms for the New Audiences

The `newsletter_audiences` taxonomy is **shared across all collections**.
Each audience sub-group needs a corresponding term.

Via CP: **Taxonomies → Newsletter Audiences → Create Term**

| Term Title | Term Slug |
|---|---|
| Culture Arts | `culture-arts` |
| Culture Music | `culture-music` |

> Slug convention: `{collection-prefix}-{sub-group}` keeps terms clearly scoped
> when all collections share one taxonomy.

The term slugs appear in the `Send To (Audiences)` field on every entry form,
so editors can target the right sub-group when creating a newsletter.

---

## Step 6 — How Audiences Map to Sub-Groups

The link between taxonomy terms and subscriber sub-groups is made at **campaign send time**
by `CampaignController@syncAudiences()`. It resolves the group from the collection handle:

```
collection handle  →  strip "_newsletters"  →  group slug
insight_newsletters  →  insight
culture_newsletters  →  culture
```

So the subscriber group slug **must match** the collection handle prefix (`culture`).

When an editor sets "Send To: Culture Arts" on an entry, the system:
1. Finds the subscriber group with slug `culture`
2. Finds the sub-group with slug matching the term slug `culture-arts`
3. Queues emails to all active subscribers in that sub-group

---

## Step 7 — Create Blade Email Templates

Create one `.blade.php` file per blueprint defined in Step 3a.

```
resources/views/emails/culture/weekly.blade.php
resources/views/emails/culture/spotlight.blade.php
```

Use an existing template as your base:
```bash
cp resources/views/emails/insight/feature-lead.blade.php \
   resources/views/emails/culture/weekly.blade.php
```

### Anatomy of an email template

```blade
@extends('emails.layout')

{{-- Product nameplate shown below the collection logo in the header band.
     Replace with: <img src="{{ asset('assets/email/culture-weekly.png') }}" ...>
     when you have a proper product logo PNG ready. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Weekly Update
    </span>
@endsection

@section('content')
    {{-- Your layout rows here --}}
@endsection
```

The layout injects these variables automatically (from `NewsletterMailable`):

| Variable | Source | Description |
|---|---|---|
| `$subject` | Entry field | Email subject line |
| `$preheader` | Entry field | Inbox preview text |
| `$content` | Entry Bard field | Main body HTML (UTM-injected) |
| `$heroImageUrl` | Entry assets field | Optional hero image URL |
| `$author` | Entry field | Byline |
| `$fromName` | config/newsletter.php | Sender display name |
| `$collectionLogo` | GlobalSet | Collection logo URL (nullable) |
| `$headerColor` | GlobalSet or config | Header background colour |
| `$unsubscribeUrl` | Signed route | One-click unsubscribe |
| `$preferencesUrl` | Signed route | Preference centre |
| `$newsletterSettings` | GlobalSet (cached) | Full settings array |

### Product logo file naming convention

Drop product-level PNG/SVG logo at:
```
public/assets/email/{collection-prefix}-{blueprint-handle}.png
```

Examples:
```
public/assets/email/culture-weekly.png
public/assets/email/culture-spotlight.png
public/assets/email/insight-feature-lead.png
```

Replace the `@section('nameplate')` text span with an `<img>` tag once the file exists.

---

## Step 8 — Upload Logo via CP (after deploying)

1. Log in to CP
2. **Globals → Newsletter Settings**
3. Upload to **Culture Collection Logo**
4. Set **Culture Brand Color** if different from the default
5. Save

Changes take effect on the next campaign send. The 1-hour cache clears automatically.

---

## Step 9 — DNS (only if new sending domain)

Skip this step if the new collection sends from a domain already verified in Elastic Email.

If using a new domain (e.g., `@dataphyte.culture`):

| Record | Type | Value |
|---|---|---|
| SPF | TXT `@` | `v=spf1 include:_spf.elasticemail.com ~all` |
| DKIM | TXT `api._domainkey` | Value from Elastic Email → Settings → Domains |
| DMARC | TXT `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com` |

Verify the domain in Elastic Email. Allow 15–60 minutes for DNS propagation.

---

## Template → Blueprint → Rendering: The Full Flow

```
Editor creates entry
    ↓ selects blueprint (e.g. "Weekly Update")
    ↓ fills in subject, content, audiences
    ↓ saves → Statamic writes all fields including hidden `template` field
              entry.data.template = "emails.culture.weekly"   ← auto-filled

Campaign send triggered
    ↓ NewsletterMailable::content() is called
    ↓ TemplateResolver::resolve($entry)
        1. Reads $entry->get('template')      → "emails.culture.weekly"
        2. view()->exists("emails.culture.weekly") → true
        → returns "emails.culture.weekly"
    ↓ Blade renders emails/culture/weekly.blade.php
    ↓ $collectionLogo resolved from GlobalSet culture_logo field
    ↓ $headerColor resolved from GlobalSet culture_brand_color field
    ↓ Email dispatched via Elastic Email
```

### Adding / editing / deleting fields on a blueprint

| Action | Effect on existing entries | Action required in template |
|---|---|---|
| Add field | New entries get it; old entries have null | Add `@if(!empty($var))` block |
| Delete field | Field hidden in CP; data orphaned until re-save | Remove references in Blade |
| Rename field | Old key orphaned; new key null on existing entries | Update variable name everywhere |
| Edit `template` field default | Only new entries get new default; old entries unchanged | Nothing |

**Never delete or rename the `template` field.** It is what `TemplateResolver` reads
to know which Blade file to render. Deleting it causes graceful degradation to the
`emails.layout` hard fallback — a mostly blank email.

---

## Verification Checklist

- [ ] `.env` has `NEWSLETTER_{NAME}_FROM_EMAIL` and `NEWSLETTER_{NAME}_FROM_NAME`
- [ ] `config/newsletter.php` has the collection entry with `brand_color`
- [ ] Config cache cleared (`php artisan config:clear`)
- [ ] `blueprintDefinitions()` in ScaffoldCollections has the new blueprints
- [ ] `handle()` in ScaffoldCollections calls `scaffoldCollection()` for new collection
- [ ] GlobalSet blueprint in ScaffoldCollections has `{name}_logo` and `{name}_brand_color` fields
- [ ] `php artisan newsletter:scaffold` run successfully
- [ ] Subscriber group + sub-groups created in CP (group slug = collection prefix)
- [ ] Taxonomy terms created in CP (one per sub-group)
- [ ] Blade template files created (one per blueprint)
- [ ] Logo uploaded via CP → Globals → Newsletter Settings
- [ ] Test email sent and received correctly before first real campaign
- [ ] DNS records added and verified (if new sending domain)

---

## What Does NOT Need Changing

| Component | Reason |
|---|---|
| Database migrations | Schema is collection-agnostic |
| Eloquent models | `Campaign::sender()` resolves any collection via config |
| `TemplateResolver` | Resolves any template via stored field or convention |
| `AudienceResolver` | Resolves any sub-group via morph map |
| `ProcessWebhookJob` | Matches events by transaction ID, not collection |
| Queue workers / Horizon | Not collection-specific |
| Analytics controllers | Query by campaign, not collection |
| GDPR controllers | Subscriber-level, not collection-specific |
