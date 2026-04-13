# Analytics & Metrics

---

## Cloudways Elastic Email Addon (SMTP Only — No Dashboard)

The Cloudways addon configures SMTP credentials only. There is no dashboard.
Elastic Email still tracks all emails sent through their SMTP server — you access that
tracking by setting up webhooks directly in your Elastic Email account (not Cloudways).

### One-time setup in elasticemail.com
Settings > Notifications > Add notification:
- URL: `https://yourdomain.com/webhooks/elastic-email`
- Events to enable: Sent, Delivered, Opened, Clicked, Bounced, Unsubscribed, Complained

All events are stored in the local `campaign_sends` table. Every metric displayed in
the CP is computed from that table — no external dashboard needed.

---

## Three Analytics Views to Build

### 1. Per-Campaign Summary

Shown on each campaign's detail page in the Statamic CP.

```
Campaign: April Insight Newsletter
Sent:         1,240   (all campaign_sends for this campaign)
Delivered:    1,198   (status: delivered | opened | clicked)
Failed:          42   (status: failed | bounced)
Opened:         542   (opened_at IS NOT NULL)
Unread:         656   (delivered but opened_at IS NULL)
```

**SQL:**
```sql
SELECT
    COUNT(*)                                                      AS total_sent,
    SUM(status IN ('delivered','opened','clicked'))               AS total_delivered,
    SUM(status IN ('failed','bounced'))                           AS total_failed,
    SUM(opened_at IS NOT NULL)                                    AS total_opened,
    SUM(status IN ('delivered','opened','clicked')
        AND opened_at IS NULL)                                    AS total_unread
FROM campaign_sends
WHERE campaign_id = :campaign_id;
```

---

### 2. Per-Subscriber History

Shown on each subscriber's detail page in the CP.
Answers: how engaged is this subscriber across all campaigns sent to them?

```
Subscriber: john@example.com
Total sent to them:   18
Total delivered:      17
Total failed:          1
Total opened:         12
```

**SQL:**
```sql
SELECT
    COUNT(*)                                         AS total_sent,
    SUM(status IN ('delivered','opened','clicked'))  AS total_delivered,
    SUM(status IN ('failed','bounced'))              AS total_failed,
    SUM(opened_at IS NOT NULL)                       AS total_opened
FROM campaign_sends
WHERE subscriber_id = :subscriber_id;
```

The subscriber detail page also lists every campaign they were sent, with individual
status per row (delivered / opened / failed).

---

### 3. Link Clicks → Google Analytics / Website Metrics

Newsletter links already point to Dataphyte Insight or Foundation websites where GA is
installed. GA receives the visit — but cannot tell it came from a newsletter unless
UTM parameters are appended to every link at send time.

**The system auto-appends UTM params when dispatching the campaign:**

```
Original link in newsletter content:
https://dataphyteinsight.com/story/headline-slug

What gets sent in the email:
https://dataphyteinsight.com/story/headline-slug
  ?utm_source=newsletter
  &utm_medium=email
  &utm_campaign=april-insight-edition
  &utm_content=topics
```

GA on the destination site picks this up under:
- Acquisition → Traffic Acquisition → Campaign
- Or Acquisition → Campaigns in GA4

**UTM values populated automatically from the campaign entry:**
| UTM param | Value |
|---|---|
| `utm_source` | `newsletter` |
| `utm_medium` | `email` |
| `utm_campaign` | campaign slug (from Statamic entry) |
| `utm_content` | audience sub-group slug |

No separate click tracking needed on the newsletter system side —
GA on the website handles it. The newsletter system tracks *whether* a link was clicked;
GA tracks *what the subscriber did after*.

---

## Webhook → Database Flow (How Metrics Arrive)

```
Elastic Email SMTP send
    ↓
Elastic Email servers detect: open / click / bounce / delivered
    ↓
POST to https://yourdomain.com/webhooks/elastic-email
    ↓
ProcessWebhookJob (queued, async)
    ↓
Updates campaign_sends row:
    - status
    - delivered_at / opened_at / clicked_at / bounced_at
    ↓
CP queries campaign_sends for display
```

---

## What Is NOT Trackable in Email

| Metric | Reason | Alternative |
|---|---|---|
| Scroll depth | Email clients strip JavaScript | Only on web version (view in browser) via Plausible/GA |
| Time spent reading | Not exposed by email clients | Litmus ($99+/month) |
| Accurate open rate | Apple MPP pre-fetches pixels since 2021 | Use click rate as primary engagement signal |

---

## Computed Rates (for CP display)

| Rate | Formula |
|---|---|
| Delivery rate | delivered / sent × 100 |
| Open rate | opened / delivered × 100 |
| Failure rate | failed / sent × 100 |
| Unread rate | unread / delivered × 100 |

Good benchmarks: delivery > 95%, open rate 20–40%, failure < 2%.
