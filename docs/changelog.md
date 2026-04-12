# Changelog

---

## Session 3 — 2026-04-12 (Active testing, bug fixes)

### Critical Bugs Fixed

#### UUID auto-generation on entry insert
- **Problem:** `SQLSTATE HY000: Field 'id' doesn't have a default value` when saving any Statamic entry via CP
- **Root cause:** Statamic Eloquent driver's `entries` table uses `char(36)` UUID primary key with no DEFAULT. `Entry::make()` creates entries without an ID; the vendor `makeModelFromContract()` only sets `id` if `$source->id()` is non-null.
- **Fix:** `app/Models/EntryModel.php` — extends `Statamic\Eloquent\Entries\EntryModel` with Laravel's `HasUuids` trait. Config `statamic/eloquent-driver.php` `entries.model` points to the custom model.
- **Commit:** `64f2b2e`

#### CP controller redirects using wrong route namespace
- **Problem:** `Route [newsletter.campaigns.show] not defined`
- **Root cause:** `Statamic::pushCpRoutes()` registers routes under the `statamic.cp.*` namespace. `redirect()->route('newsletter.campaigns.show')` skips the prefix.
- **Fix:** All CP controller redirects now use `cp_route('newsletter.campaigns.show', $campaign)` which resolves to `statamic.cp.newsletter.campaigns.show`.
- **Files:** `CampaignController.php`, `GdprController.php`
- **Commit:** `15671b0`

#### Campaign `from_email` NOT NULL violation
- **Problem:** `SQLSTATE 23000: Column 'from_email' cannot be null` when sender override fields left blank
- **Fix:** Migration `2026_04_12_000001` makes `from_name` and `from_email` nullable. `blankToNull()` helper in controller converts empty form strings to NULL.
- **Commit:** `7021c5a`

#### Undefined constant "first_name" in Blade
- **Problem:** `{{first_name}}` merge tag written literally in `show.blade.php` hint text → Blade compiled it as `echo e(first_name)` → PHP "Undefined constant"
- **Fix:** Use `@{{first_name}}` in Blade files to output literal braces without evaluation. The merge tags in Bard content are safe (passed as a PHP string to `{!! $content !!}`).
- **Commit:** `22f2f06`

#### `$sentDate` undefined in email templates
- **Problem:** Stale Blade view cache could serve compiled view before `$sentDate` was in the mailable's `with` array
- **Fix:** All 9 email templates now use `{{ $sentDate ?? now()->format('F j, Y') }}` defensive null coalescing. View cache cleared.
- **Commit:** `f1f3a4c`

#### `$preferencesUrl` undefined on direct URL preview
- **Problem:** Statamic CP entry preview renders the blueprint's template directly (without the mailable) → `$preferencesUrl` undefined
- **Fix:** `emails/layout.blade.php` footer now uses `$preferencesUrl ?? '#'` and `$unsubscribeUrl ?? '#'`
- **Commit:** `101a548`

### Features Added

#### Browser email preview
- `GET /cp/newsletter/campaigns/{campaign}/preview` — renders full email HTML in browser with blue banner showing template name. Links disabled (`href="#"`).
- Controller: `CampaignController::preview()`
- Route name: `newsletter.campaigns.preview`

#### Subscriber personalisation
- **Merge tags in Bard body:** `{{first_name}}` `{{last_name}}` `{{full_name}}` `{{email}}` — replaced via `applyMergeTags()` in `NewsletterMailable`
- **Blade template variables:** `$subscriberFirstName`, `$subscriberLastName`, `$subscriberFullName`, `$subscriberEmail`

#### Reset stuck campaign to draft
- `POST /cp/newsletter/campaigns/{campaign}/reset` → `resetToDraft()`
- Available when campaign status is `sending` or `failed`
- Shown as yellow "Stuck?" card on campaign show page

#### Mailtrap dev mail
- `config/mail.php`: new `mailtrap` SMTP mailer reading `MAILTRAP_USERNAME`/`MAILTRAP_PASSWORD` (falls back to `MAIL_USERNAME`/`MAIL_PASSWORD`)
- `QUEUE_CONNECTION=sync` set in `.env` for dev — jobs run immediately, no worker needed

#### Blueprint name in campaign entry picker
- `allEntries()` in `CampaignController` now includes `blueprint` title
- Entry dropdown shows: `[Breaking News] Apr 11, 2026 — Breaking: Testing`

### Alpine.js → Vanilla JS Replacements

Statamic's CP owns the Alpine.js instance — custom `x-data` components inside it don't fire reliably.

| Component | Was | Now |
|---|---|---|
| Campaign create/edit entry selector | `x-for` + `filteredEntries` computed | `populateEntries()` vanilla JS |
| Campaign show test-send dropdown | `x-data="{ open: false }"` + `@click` | `onclick` toggle + document click listener |

Alpine is still used for audience group show/hide and schedule radio (those interact with Statamic's own Alpine scope cleanly).

---

## Session 2 — 2026-04-11/12 (Full system build)

### Architecture Decisions

- **Blueprint = Template selector** — each Statamic blueprint has a hidden `template` field auto-filled on first save. No manual template field needed.
- **Logos:** Collection logos editor-controlled via `newsletter_settings` GlobalSet (cached 1hr). Product nameplates hardcoded per Blade template.
- **Audience:** Selected at campaign level only — entry is pure content.
- **UUID fix:** `App\Models\EntryModel` + `HasUuids` trait.

### Files Created
- `app/Models/EntryModel.php`
- `app/Services/Newsletter/TemplateResolver.php`
- `app/Mail/NewsletterMailable.php` (full)
- `app/Console/Commands/Newsletter/ScaffoldCollections.php` (full rewrite)
- `resources/views/emails/layout.blade.php`
- `resources/views/emails/insight/` (6 templates)
- `resources/views/emails/foundation/` (3 templates)
- `resources/views/landing/index.blade.php`
- All CP campaign views (create, edit, show, index)
- 69 tests across 5 test files — all passing

---

## Session 1 — Initial scaffold

Base Laravel + Statamic installation, DB migrations, models, basic routing.
