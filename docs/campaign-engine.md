# Campaign Engine

---

## Campaign Lifecycle

```
Draft → Scheduled → Sending → Sent
  ↑          |          |
  |     (cancel)   (reset to draft if stuck)
  +←←←←←←←←←←←←←←←←←←←←←+
```

### Statuses

| Status | Meaning |
|---|---|
| `draft` | Being created/edited, not yet sent |
| `scheduled` | Has `scheduled_at` datetime set, waiting to dispatch |
| `sending` | `DispatchCampaignJob` running, emails being sent |
| `sent` | All emails dispatched |
| `failed` | Job failed after all retries |

> If a campaign gets stuck in `sending` (e.g. queue worker not running), use the **"Reset to Draft"** button on the campaign show page → it resets status and `sent_at` to null so you can resend.

---

## The Send Workflow (Actual)

### Step 1 — Write the content
1. Go to Statamic CP → Collections → Foundation Newsletters (or Insight Newsletters)
2. Click **New Entry**
3. Choose a **blueprint** — this determines both the content fields and the email template:
   - **Insight:** Feature Lead, Single Story, Digest, Newsroom Roundup, Breaking News, Data Story
   - **Foundation:** Weekly Update, Activities, Project Update
4. Fill in the content fields (title, subject, body via Bard, preheader, hero image, author)
5. **Publish** the entry

### Step 2 — Create and send the campaign
1. Go to Newsletter → Campaigns → **New Campaign**
2. Pick the **Collection** (Insight or Foundation)
3. Pick the **Content Entry** — dropdown shows entries as `[Blueprint Name] Date — Title`
4. Subject auto-fills from the entry (editable)
5. Pick **Audience** — either "Send to All" or specific sub-groups
6. **From / Reply-To** — leave blank to use collection defaults from `config/newsletter.php`
7. Choose action: **Save Draft**, **Schedule**, or **Send Now**

---

## Sender Resolution

`Campaign::sender()` resolves in priority order:

```
1. campaign.from_email / campaign.from_name  (if not null — editor override)
2. config('newsletter.collections.{collection}.from_email')  (collection default)
3. config('newsletter.from_email')  (global fallback)
```

`from_email`, `from_name`, and `reply_to` are **nullable** in the DB. Leaving them blank stores NULL and falls through to config defaults.

---

## Blueprint → Template Mapping

Each blueprint has a hidden `template` field auto-filled on first save. `TemplateResolver` resolves in priority order:

```
1. Entry's stored template field  (if blade view exists)
2. Convention: emails.{collection_without_newsletters}.{blueprint_handle_with_hyphens}
3. emails.{collection}.default
4. emails.layout  (bare fallback)
```

**Blueprint → template map:**

| Collection | Blueprint | Template |
|---|---|---|
| insight_newsletters | feature_lead | `emails.insight.feature-lead` |
| insight_newsletters | single_story | `emails.insight.single-story` |
| insight_newsletters | digest | `emails.insight.digest` |
| insight_newsletters | roundup | `emails.insight.roundup` |
| insight_newsletters | breaking_news | `emails.insight.breaking` |
| insight_newsletters | data_story | `emails.insight.data-story` |
| foundation_newsletters | weekly | `emails.foundation.weekly` |
| foundation_newsletters | activities | `emails.foundation.activities` |
| foundation_newsletters | project_update | `emails.foundation.project-update` |

---

## Email Template Variables

All templates receive these variables from `NewsletterMailable::content()`:

| Variable | Source |
|---|---|
| `$subject` | Campaign subject |
| `$preheader` | Entry `preheader` field |
| `$heroImageUrl` | Entry `hero_image` asset |
| `$content` | Entry Bard body (HTML, UTM-injected, merge tags applied) |
| `$author` | Entry `author` field, fallback to sender from_name |
| `$fromName` | Resolved sender name |
| `$sentDate` | `campaign.sent_at` formatted, fallback `now()` |
| `$collectionLogo` | GlobalSet `newsletter_settings.{collection}_logo` URL |
| `$headerColor` | GlobalSet brand color or config fallback |
| `$unsubscribeUrl` | Signed URL → `newsletter.unsubscribe.show` |
| `$preferencesUrl` | Signed URL → `newsletter.preferences.show` |
| `$subscriberFirstName` | `subscriber.first_name` |
| `$subscriberLastName` | `subscriber.last_name` |
| `$subscriberFullName` | `subscriber.full_name` or email fallback |
| `$subscriberEmail` | `subscriber.email` |
| `$newsletterSettings` | Full GlobalSet array (injected by view composer) |

### Merge Tags (in Bard body content)

Type these directly in the Bard editor body — they are replaced before send:

| Tag | Replaced with |
|---|---|
| `{{first_name}}` | Subscriber first name (blank if not set) |
| `{{last_name}}` | Subscriber last name (blank if not set) |
| `{{full_name}}` | Subscriber full name (email if not set) |
| `{{email}}` | Subscriber email address |

> **In Blade template files**, always write `@{{first_name}}` (with `@` prefix) to output the literal text. Without `@`, Blade tries to evaluate `first_name` as a PHP constant.

---

## Audience Resolution

`AudienceResolver::resolve(Campaign $campaign)` works as follows:

```
1. Collect all campaign_audiences rows for this campaign
2. If any row has send_to_all = true:
     → Resolve the group from collection handle (insight → insight-subscribers, foundation → foundation)
     → Return all active subscribers in that group (via sub_group pivot)
3. For each targetable_type = subscriber_sub_group:
     → Get all active subscribers in that sub_group
4. Merge + deduplicate by subscriber.id
5. Return unique subscriber collection
```

A subscriber in multiple targeted sub-groups receives **exactly one email** per campaign.

---

## Job Architecture

### DispatchCampaignJob (queue: `campaigns`)

1. Load campaign, resolve audience via `AudienceResolver`
2. Update `total_recipients`
3. Create `campaign_sends` rows (`status = pending`) for each subscriber
4. Dispatch `SendNewsletterEmailJob` per subscriber
5. Set campaign `status = sent`

### SendNewsletterEmailJob (queue: `emails`)

1. Load `CampaignSend` with campaign + subscriber
2. Skip if status not `pending`/`failed` (idempotency)
3. Mark `status = sending`, `sent_at = now()`
4. Instantiate `NewsletterMailable`, send via configured mailer
5. Extract Elastic Email transaction ID from response headers
6. Mark `status = sent`; on failure: mark `status = failed`, rethrow for retry

**Middleware:** `RateLimited('newsletter-emails')` — max 15 concurrent

---

## Browser Preview

`GET /cp/newsletter/campaigns/{campaign}/preview`

Renders the resolved email template in the browser with a blue banner. Merge tags shown as `[First Name]` etc. Unsubscribe/Preferences links point to `#`. Safe for sharing for editorial review.

---

## Test Send

`POST /cp/newsletter/campaigns/{campaign}/test-send`

Sends a real email via the configured mailer to a single address. If the address matches an existing subscriber, real subscriber data is used (including merge tags). Otherwise a synthetic subscriber is created (name: "Test Recipient").

Does **not** create a `campaign_send` record and does **not** count toward campaign stats.

---

## Scheduling

### Immediate Send
Click **Send Now** on the campaign create/edit form → sets `sent_at = now()`, status → `sending`, dispatches `DispatchCampaignJob`.

### Scheduled Send
Select **Schedule** radio → pick a future datetime → sets `scheduled_at`, status → `scheduled`.

The Laravel scheduler (`php artisan schedule:run` every minute via cron) dispatches `DispatchCampaignJob` when `scheduled_at ≤ now()`.

---

## CP Route Note

All campaign controller redirects use `cp_route()` (not `route()`). Routes are registered via `Statamic::pushCpRoutes()` which adds the `statamic.cp.` namespace prefix. `cp_route('newsletter.campaigns.show', $campaign)` resolves to `statamic.cp.newsletter.campaigns.show`.
