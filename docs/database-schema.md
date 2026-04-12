# Database Schema

All subscriber and campaign data lives in MySQL (not Statamic's flat files) for performance at scale.

---

## Tables

### subscriber_groups

Top-level groupings.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK, auto-increment |
| name | varchar(255) | e.g., "Insight Subscribers", "Foundation" |
| slug | varchar(255) | unique, URL-safe |
| description | text, nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Seed data:**
- Insight Subscribers
- Foundation

---

### subscriber_sub_groups

Sub-groups within a parent group.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| subscriber_group_id | bigint unsigned | FK -> subscriber_groups.id |
| name | varchar(255) | e.g., "Topics", "Marina & Maitama", "Weekly" |
| slug | varchar(255) | unique within parent group |
| description | text, nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Seed data:**
- Insight Subscribers -> Topics, Marina & Maitama, SenorRita
- Foundation -> Weekly, Activities

---

### subscribers

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| email | varchar(255) | unique, indexed |
| first_name | varchar(255), nullable | |
| last_name | varchar(255), nullable | |
| status | enum | active, unsubscribed, bounced, complained |
| confirmation_token | varchar(64), nullable | For double opt-in |
| confirmed_at | timestamp, nullable | Proof of consent |
| unsubscribed_at | timestamp, nullable | |
| ip_address | varchar(45), nullable | Consent record (IPv6 support) |
| user_agent | text, nullable | Consent record |
| metadata | json, nullable | Extensible extra data |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### subscriber_sub_group (pivot)

Many-to-many: subscribers belong to multiple sub-groups.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| subscriber_id | bigint unsigned | FK -> subscribers.id |
| subscriber_sub_group_id | bigint unsigned | FK -> subscriber_sub_groups.id |
| subscribed_at | timestamp | When they joined this sub-group |
| unsubscribed_at | timestamp, nullable | When they left (null = still active) |

**Unique index:** (subscriber_id, subscriber_sub_group_id)

---

### campaigns

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | Internal name |
| subject | varchar(255) | Email subject line |
| from_name | varchar(255) | Sender display name |
| from_email | varchar(255) | Sender email address |
| reply_to | varchar(255), nullable | |
| content_html | longtext | Rendered HTML content |
| content_text | longtext, nullable | Plain text fallback |
| status | enum | draft, scheduled, sending, sent, cancelled |
| scheduled_at | timestamp, nullable | When to send |
| sent_at | timestamp, nullable | When actually sent |
| total_recipients | int unsigned, default 0 | Cached count |
| created_by | varchar(255), nullable | Statamic user ID |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### campaign_audiences

Defines which groups/sub-groups a campaign targets. Uses polymorphic targeting.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| campaign_id | bigint unsigned | FK -> campaigns.id |
| targetable_type | varchar(255) | `SubscriberGroup` or `SubscriberSubGroup` |
| targetable_id | bigint unsigned | FK -> the target group/sub-group |
| send_to_all | boolean, default false | If true, send to ALL subscribers (ignores target) |

**Audience resolution logic:**
1. If any row has `send_to_all = true` -> send to all active subscribers
2. If `targetable_type = SubscriberGroup` -> send to all active subscribers in any sub-group of that group
3. If `targetable_type = SubscriberSubGroup` -> send to active subscribers in that specific sub-group
4. Deduplicate: a subscriber in multiple targeted sub-groups receives only ONE email

---

### campaign_sends

One row per email sent. The core tracking table.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| campaign_id | bigint unsigned | FK -> campaigns.id |
| subscriber_id | bigint unsigned | FK -> subscribers.id |
| status | enum | queued, sent, delivered, opened, clicked, bounced, failed, complained |
| elastic_email_message_id | varchar(255), nullable | For correlating webhooks |
| sent_at | timestamp, nullable | |
| delivered_at | timestamp, nullable | |
| opened_at | timestamp, nullable | First open |
| clicked_at | timestamp, nullable | First click |
| bounced_at | timestamp, nullable | |
| failed_at | timestamp, nullable | |
| bounce_reason | text, nullable | |
| created_at | timestamp | |

**Indexes:**
- (campaign_id, status) - for stats aggregation
- (subscriber_id, campaign_id) - unique, prevents duplicate sends
- (elastic_email_message_id) - for webhook lookups

---

### campaign_link_clicks

Detailed per-link click tracking.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| campaign_send_id | bigint unsigned | FK -> campaign_sends.id |
| url | text | The clicked URL |
| clicked_at | timestamp | |
| ip_address | varchar(45), nullable | |
| user_agent | text, nullable | |

---

### email_templates

Reusable newsletter templates.

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | |
| slug | varchar(255), unique | |
| description | text, nullable | |
| content_html | longtext | Blade template content |
| is_default | boolean, default false | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Relationships Summary

```
SubscriberGroup 1---* SubscriberSubGroup
SubscriberSubGroup *---* Subscriber (via pivot)
Campaign 1---* CampaignAudience
Campaign 1---* CampaignSend
CampaignSend *---1 Subscriber
CampaignSend 1---* CampaignLinkClick
```
