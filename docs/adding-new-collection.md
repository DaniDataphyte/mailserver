# Adding a New Newsletter Collection

This document is the complete reference for adding a new newsletter collection to the
system. Follow the steps in order. Nothing outside this checklist needs to change.

---

## Overview

Each newsletter collection maps to:
- A **Statamic collection** (where editors write entries)
- A **subscriber group** (who receives the emails)
- A **sender identity** (which from address and name is used)
- One or more **Blade email templates** (how the email looks)

Current collections:

| Collection Handle | Sender Name | From Address |
|---|---|---|
| `insight_newsletters` | Dataphyte Insight | newsletter@dataphyte.com |
| `foundation_newsletters` | Dataphyte Foundation | newsletter@dataphyte.org |

---

## Step 1 — Add Sender Identity to `.env`

Add three lines to `.env`. Replace `[NAME]` with a short uppercase identifier
for the new collection (e.g., `WEEKLY`, `SPORTS`, `CULTURE`).

```dotenv
NEWSLETTER_[NAME]_FROM_EMAIL=newsletter@yourdomain.com
NEWSLETTER_[NAME]_FROM_NAME="Your Collection Name"
NEWSLETTER_[NAME]_REPLY_TO=
```

**Example — adding a "Culture" collection:**
```dotenv
NEWSLETTER_CULTURE_FROM_EMAIL=newsletter@dataphyte.com
NEWSLETTER_CULTURE_FROM_NAME="Dataphyte Culture"
NEWSLETTER_CULTURE_REPLY_TO=
```

> `REPLY_TO` can be left blank. When empty, replies go to the from address.

---

## Step 2 — Register in `config/newsletter.php`

Open `config/newsletter.php` and add an entry inside the `collections` array.
The **array key must exactly match the Statamic collection handle** you will create in Step 3.

```php
'collections' => [

    'insight_newsletters' => [
        'from_email' => env('NEWSLETTER_INSIGHT_FROM_EMAIL', 'newsletter@dataphyte.com'),
        'from_name'  => env('NEWSLETTER_INSIGHT_FROM_NAME', 'Dataphyte Insight'),
        'reply_to'   => env('NEWSLETTER_INSIGHT_REPLY_TO', ''),
    ],

    'foundation_newsletters' => [
        'from_email' => env('NEWSLETTER_FOUNDATION_FROM_EMAIL', 'newsletter@dataphyte.org'),
        'from_name'  => env('NEWSLETTER_FOUNDATION_FROM_NAME', 'Dataphyte Foundation'),
        'reply_to'   => env('NEWSLETTER_FOUNDATION_REPLY_TO', ''),
    ],

    // Add new collection here ↓
    'culture_newsletters' => [
        'from_email' => env('NEWSLETTER_CULTURE_FROM_EMAIL', 'newsletter@dataphyte.com'),
        'from_name'  => env('NEWSLETTER_CULTURE_FROM_NAME', 'Dataphyte Culture'),
        'reply_to'   => env('NEWSLETTER_CULTURE_REPLY_TO', ''),
    ],

],
```

Then clear the config cache:
```bash
php artisan config:clear
```

---

## Step 3 — Create the Statamic Collection

In the Statamic CP:

1. **Content > Collections > Create Collection**
2. Set the handle to match the key used in `config/newsletter.php` (e.g., `culture_newsletters`)
3. Set a title (e.g., "Culture Newsletters")
4. Route: `/newsletters/culture/{slug}` (for the web/view-in-browser URL)
5. Save

### Create the Blueprint

Each collection needs a Blueprint defining what editors fill in per newsletter entry.

Go to the new collection > Blueprint > Add fields:

| Field Handle | Field Type | Required | Notes |
|---|---|---|---|
| `subject` | Text | Yes | Email subject line |
| `email_template` | Select | Yes | See Step 5 for option values |
| `preheader` | Text | No | Inbox preview text (hidden in email body) |
| `content` | Bard | Yes | Main newsletter body |
| `hero_image` | Assets | No | Optional header image |
| `author` | Text | No | Byline |
| `reply_to` | Text | No | Per-campaign reply override |
| `audiences` | Taxonomy | Yes | Taxonomy: `newsletter_audiences` |
| `send_to_all` | Toggle | No | Send to entire group, ignoring sub-groups |
| `scheduled_at` | Date/Time | No | Leave blank for manual send |

### Add Audience Terms to the Taxonomy

If this collection has sub-groups (editorial verticals, categories, etc.):

1. CP > Taxonomies > Newsletter Audiences > Create Term
2. Add a term for each sub-group (e.g., `culture-arts`, `culture-music`)
3. These terms will appear in the `audiences` field when creating entries

---

## Step 4 — Create the Subscriber Group in the Database

The subscriber group tells the system who receives this collection's newsletters.

### Via the CP (Phase 2 UI — once built)
1. Newsletter > Groups > Create Group
2. Name: "Culture" (or whatever the collection is)
3. Slug: `culture` (lowercase, hyphenated)
4. Save
5. Add sub-groups under it as needed

### Via Tinker (available now)
```bash
php artisan tinker
```
```php
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;

$group = SubscriberGroup::create([
    'name' => 'Culture',
    'slug' => 'culture',
    'description' => 'Dataphyte Culture newsletter subscribers',
]);

// Add sub-groups
SubscriberSubGroup::create(['subscriber_group_id' => $group->id, 'name' => 'Arts',  'slug' => 'arts']);
SubscriberSubGroup::create(['subscriber_group_id' => $group->id, 'name' => 'Music', 'slug' => 'music']);
```

---

## Step 5 — Create Email Template Blade Files

### Create the directory
```
resources/views/emails/culture/
```

### Create at least one layout
```
resources/views/emails/culture/default.blade.php
```

Use an existing collection template as your starting point:
```bash
cp resources/views/emails/insight/feature-lead.blade.php \
   resources/views/emails/culture/default.blade.php
```

Edit the copied file to adjust the layout, colours, or structure as needed.

### Register in the `email_templates` table

```bash
php artisan tinker
```
```php
use App\Models\EmailTemplate;

EmailTemplate::create([
    'name'        => 'Culture — Default',
    'slug'        => 'culture-default',
    'description' => 'Standard single-column layout for Culture newsletters',
    'blade_view'  => 'emails.culture.default',
    'collection'  => 'culture_newsletters',
    'is_default'  => true,
]);
```

### Update the Blueprint select field options

Go back to the Blueprint created in Step 3 > `email_template` field > Options.
Add the new template:

```
emails/culture/default: Culture — Default
```

---

## Step 6 — DNS (Only if using a new sending domain)

If the new collection sends from a domain not already verified in Elastic Email
(e.g., a brand new `@newdomain.com`), you must:

1. **Add DNS records** at your DNS provider:

   | Type | Host | Value |
   |---|---|---|
   | TXT | `@` | `v=spf1 include:_spf.elasticemail.com ~all` (merge with existing SPF if present) |
   | TXT | `api._domainkey.newdomain.com` | DKIM value from Elastic Email |
   | TXT | `_dmarc.newdomain.com` | `v=DMARC1; p=none; rua=mailto:dmarc@newdomain.com` |

2. **Verify the domain** in Elastic Email:
   - Settings > Domains > Add Domain
   - Enter the new sending domain
   - Complete verification
   - Set up the bounce/return-path domain

3. **Allow 15–60 minutes** for DNS propagation before testing.

> If the new collection sends from an already-verified domain (e.g., another
> `@dataphyte.com` address), skip this step entirely.

---

## Verification Checklist

Before sending the first campaign from a new collection, confirm:

- [ ] `.env` has `NEWSLETTER_[NAME]_FROM_EMAIL` and `NEWSLETTER_[NAME]_FROM_NAME`
- [ ] `config/newsletter.php` has the collection entry with matching handle
- [ ] Config cache cleared (`php artisan config:clear`)
- [ ] Statamic collection exists with correct handle
- [ ] Blueprint has all required fields including `email_template` and `audiences`
- [ ] Audience taxonomy terms created for all sub-groups
- [ ] Subscriber group and sub-groups exist in the database
- [ ] At least one Blade template file exists for the collection
- [ ] Template registered in `email_templates` table
- [ ] Blueprint `email_template` select field includes the new template option
- [ ] DNS records added and verified (if new sending domain)
- [ ] Test email sent and received correctly before first campaign

---

## What Does NOT Need Changing

| Component | Reason |
|---|---|
| Database migrations | Schema is collection-agnostic |
| Eloquent models | `Campaign::sender()` resolves any collection via config |
| `ElasticEmailTransport` | Sends any email regardless of collection |
| Queue workers / Horizon | Queues are not collection-specific |
| Webhook handler | Matches events by transaction ID, not collection |
| Subscriber import logic | Imports to sub-groups, not collections |
| Analytics queries | Query `campaign_sends` filtered by campaign, not collection |
