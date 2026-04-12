# Mailserver - Newsletter Management System

## Tech Stack
- **CMS:** Statamic (Antlers templating)
- **Backend:** Laravel
- **Frontend:** Alpine.js, Tailwind CSS, Blade
- **Database:** MySQL
- **Cache/Queue:** Redis
- **Hosting:** Cloudways VPS
- **Email Delivery:** Elastic Email (API v4)

---

## Subscriber Groupings

### 1. Insight Subscribers
Can send to all Insight Subscribers or individual sub-groups:
- **Topics** - editorial vertical
- **Marina & Maitama** - editorial vertical
- **SenorRita** - editorial vertical (SenorRita brand)

### 2. Foundation
- **Weekly** - weekly foundation digest
- **Activities** - foundation activities and events

### Subscriber Source Mapping
- Subscribers from **Dataphyte Insight website** → Insight Subscribers group
- Subscribers from **Foundation website** → Foundation group
- Sub-group assignment based on options selected at source
- Contacts are **imported by admins** (CSV upload or manual entry), not self-subscribed via form
- Groups and sub-groups are not exhaustive — new ones can be added in the CP without code changes

### Newsletter Sending Logic
- Send to a specific sub-group (e.g., only "Topics")
- Send to all sub-groups within a group (e.g., all Insight Subscribers)
- Send to all subscribers across all groups

---

## Email Links in Every Newsletter

| Link | Type | Source |
|---|---|---|
| Unsubscribe | Signed URL per subscriber | System-generated |
| Manage preferences | Signed URL per subscriber | System-generated |
| View in browser | Stored campaign HTML | System route |
| Social / website links | Static org links | Statamic globals (managed in CP) |
| Physical address | Required by CAN-SPAM | Statamic globals (managed in CP) |

---

## What's Needed Beyond Base Statamic/Laravel

### Required Laravel Packages

| Package | Purpose |
|---|---|
| `flexflux/laravel-elastic-email` | Laravel mail driver for Elastic Email API |
| `elasticemail/elasticemail-php` | Official Elastic Email PHP SDK (webhooks, stats, contacts) |
| `laravel/horizon` | Redis queue dashboard and worker management |
| `spatie/laravel-webhook-client` | Receive/process Elastic Email webhook callbacks |
| `spatie/laravel-rate-limited-job-middleware` | Throttle email sends (Elastic Email max 20 concurrent) |
| `stevebauman/purify` | Sanitize HTML in newsletter content |

### Optional but Recommended Packages

| Package | Purpose |
|---|---|
| `maatwebsite/excel` | CSV import/export of subscribers |
| `spatie/laravel-activitylog` | Audit trail for campaigns and subscriber changes |
| `spatie/laravel-backup` | Automated database backups |

### DNS / Email Authentication
- **SPF Record:** `v=spf1 include:_spf.elasticemail.com ~all`
- **DKIM Record:** TXT record provided by Elastic Email during domain verification
- **DMARC Record:** `v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@yourdomain.com; pct=100`
- **Domain Verification:** Complete in Elastic Email dashboard (Settings > Domains)

### Cloudways Configuration
- Enable Redis (Server > Settings & Packages > Packages)
- Enable Supervisord for queue workers
- Add Laravel cron: `* * * * * php artisan schedule:run`
- PHP: `max_execution_time=120`, `memory_limit=256M`

### Third-Party Services Needed
- **DNS Provider** (e.g., Cloudflare) for SPF/DKIM/DMARC records
- **Maizzle** (optional) - Tailwind-first email template framework
- **UptimeRobot** (recommended) - Monitor webhook endpoint availability

---

## Database Schema (MySQL - beyond Statamic flat files)

| Table | Purpose |
|---|---|
| `subscriber_groups` | Top-level groupings (Insight Subscribers, Foundation) |
| `subscriber_sub_groups` | Sub-groups within groups (Topics, Weekly, etc.) |
| `subscribers` | Email, name, status, confirmation, consent data |
| `subscriber_sub_group` | Pivot: subscribers <-> sub-groups (many-to-many) |
| `campaigns` | Newsletter campaigns (draft/scheduled/sending/sent) |
| `campaign_audiences` | Which groups/sub-groups a campaign targets |
| `campaign_sends` | Individual send records with tracking status |
| `campaign_link_clicks` | Detailed click tracking per link |
| `email_templates` | Reusable newsletter templates |

---

## Redis Queue Architecture

| Queue | Purpose | Priority |
|---|---|---|
| `campaigns` | Campaign dispatch (fans out individual emails) | High |
| `emails` | Individual email send jobs | Default |
| `webhooks` | Inbound Elastic Email webhook processing | High |
| `tracking` | Deferred tracking updates (batch DB writes) | Low |

---

## Scheduled Tasks

| Schedule | Task | Purpose |
|---|---|---|
| Every 5 min | `campaigns:dispatch-scheduled` | Send campaigns at scheduled time |
| Hourly | `stats:sync-elastic-email` | Reconcile stats with Elastic Email API |
| Daily | `subscribers:cleanup-unconfirmed` | Remove unconfirmed subscribers after 7 days |
| Daily | `queue:prune-failed --hours=168` | Clean old failed jobs |
| Weekly | `campaigns:generate-reports` | Weekly campaign performance digest |

---

## Compliance Requirements

### CAN-SPAM
- Physical mailing address in every email
- Clear unsubscribe link in every email
- One-click unsubscribe via `List-Unsubscribe` header (RFC 8058)
- Accurate From/Subject lines

### GDPR
- Double opt-in (email confirmation)
- Consent records (IP, timestamp, user agent)
- Right to erasure (delete subscriber + all data)
- Data export (JSON/CSV)
- Privacy policy link on subscription forms
- Preference center for group management

### Security
- CSRF protection on all forms
- Rate-limited subscription endpoints
- Signed URLs for unsubscribe links
- Webhook origin verification
- Statamic CP access gated by roles/permissions
- Auto-suppress bounced/complained subscribers

---

## Implementation Phases

### Phase 1: Foundation (Week 1-2)
- [ ] Install Statamic on Laravel
- [ ] Install and configure Elastic Email packages
- [ ] Set up DNS records (SPF, DKIM, DMARC)
- [ ] Verify domain in Elastic Email
- [ ] Create all database migrations
- [ ] Create Eloquent models
- [ ] Configure Redis and queue connections

### Phase 2: Subscriber Management (Week 2-3)
- [ ] Subscriber CRUD (model, controller, CP views)
- [ ] Group/sub-group management
- [ ] Public subscription form (Alpine.js)
- [ ] Double opt-in flow
- [ ] Unsubscribe flow with signed URLs
- [ ] Preference center page

### Phase 3: Campaign Engine (Week 3-5)
- [x] Scaffold collections, blueprints, taxonomy, terms to DB (`newsletter:scaffold`)
- [x] Email template system — 5 Blade templates (insight: feature-lead, digest, single-story; foundation: weekly, activities)
- [x] `NewsletterMailable` — envelope, content, headers (List-Unsubscribe), UTM injection
- [x] `AudienceResolver` service — dedup subscriber IDs from group/sub-group targets
- [x] `DispatchCampaignJob` — bulk CampaignSend creation, fan-out to email queue
- [x] `SendNewsletterEmailJob` — rate-limited (300/min), 3 retries, backoff
- [x] `campaigns:dispatch-scheduled` artisan command, scheduled every 5 min
- [x] Morph map registered (subscriber_group / subscriber_sub_group)
- [x] Campaign CRUD controllers + CP views (index, create, edit, show) + 9 routes
- [x] Email templates expanded: Insight ×5 (feature-lead, single-story, digest, roundup, breaking, data-story), Foundation ×3 (weekly, activities, project-update)
- [ ] Configure Supervisor on Cloudways
- [ ] Set up cron job on Cloudways

### Phase 4: Tracking & Analytics (Week 5-6)
- [x] `webhook_logs` migration + `WebhookLog` model
- [x] `WebhookController` — receive POST, verify secret, log raw payload, queue job
- [x] `ProcessWebhookJob` — maps 15+ Elastic Email event variants, updates campaign_sends, records link clicks, auto-suppresses bounces/complaints/unsubscribes
- [x] `SyncCampaignStats` command (`campaigns:sync-stats --hours=2`) — hourly fallback reconciliation via API
- [x] `AnalyticsController` — overview dashboard, per-campaign detail, webhook log viewer
- [x] Analytics CP views — KPI cards, bar charts, open-time distribution, link click table, failed sends, webhook health panel
- [x] Analytics nav item + 3 CP routes + public webhook route
- [x] `ELASTIC_EMAIL_WEBHOOK_SECRET` added to .env + newsletter config

### Phase 5: Polish & Compliance (Week 6-7)
- [x] List-Unsubscribe + List-Unsubscribe-Post headers (RFC 8058) — in NewsletterMailable
- [x] Physical address footer — reads `NEWSLETTER_PHYSICAL_ADDRESS` from .env, rendered in all templates
- [x] GDPR data export — JSON download of profile, subscriptions, campaign history
- [x] GDPR erasure (Art. 17) — anonymises PII, detaches sub-groups, `erased` status; confirmation form with typed ERASE check
- [x] CSV import/export — built in Phase 2 (ImportController)
- [x] CP dashboard widget (`newsletter`) — KPI cards, recent campaigns, subscriber counts, webhook health; auto-discovered from app/Widgets/
- [x] Test send — `newsletter:test-send` CLI command + "Send Test" dropdown on campaign show page
- [ ] End-to-end test with real Elastic Email credentials (requires live .env values)

---

## Feature Documentation
Detailed implementation docs for each feature are in `/docs/`:
- [Statamic Content Architecture](docs/statamic-content-architecture.md)
- [Analytics & Metrics](docs/analytics.md)
- [Database Schema](docs/database-schema.md)
- [Elastic Email Integration](docs/elastic-email-integration.md)
- [Subscriber Management](docs/subscriber-management.md)
- [Campaign Engine](docs/campaign-engine.md)
- [Queue Architecture](docs/queue-architecture.md)
- [Tracking & Webhooks](docs/tracking-webhooks.md)
- [Compliance](docs/compliance.md)
- [Statamic CP Customization](docs/statamic-cp-customization.md)
- [Cloudways Deployment](docs/cloudways-deployment.md)
- [DNS Setup](docs/dns-setup.md)
- [Adding a New Collection](docs/adding-new-collection.md)
