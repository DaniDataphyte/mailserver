# Compliance (GDPR, CAN-SPAM, PECR)

---

## CAN-SPAM Requirements

Every marketing email MUST include:

1. **Physical mailing address** in the footer
2. **Clear unsubscribe mechanism** - visible link, works immediately
3. **Accurate From and Subject lines** - no deception
4. **Identification as advertisement** (if applicable)
5. **Honor opt-out within 10 business days** (implement immediate processing)

### One-Click Unsubscribe (RFC 8058)
Add to every email's headers:
```
List-Unsubscribe: <mailto:unsubscribe@yourdomain.com>, <https://yourdomain.com/unsubscribe/{token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

Gmail, Yahoo, and other major providers require this as of February 2024.

---

## GDPR Requirements

### Lawful Basis: Consent
- **Double opt-in** is the gold standard
- Store proof of consent: IP address, timestamp, user agent, what they consented to

### Consent Records (stored in `subscribers` table)
- `confirmed_at` - when they confirmed
- `ip_address` - IP at time of subscription
- `user_agent` - browser/device info
- Pivot table records which sub-groups they opted into and when

### Right to Access (Article 15)
- Build a data export feature in the admin CP
- Export subscriber's data as JSON or CSV
- Include: personal data, subscription history, campaign send history

### Right to Erasure (Article 17)
- Admin action: "Delete Subscriber"
- Permanently removes:
  - Subscriber record
  - All pivot table entries
  - All campaign_sends for that subscriber
  - All campaign_link_clicks for that subscriber
- Log that erasure was performed (without storing the erased data)

### Right to Rectification (Article 16)
- Preference center allows subscribers to update their name/email
- Admin can also edit subscriber details

### Data Minimization
- Only collect what's needed: email, first_name, last_name
- The `metadata` JSON field is for future needs, not speculative collection

---

## Privacy Policy

Link to privacy policy on:
- Every subscription form
- Every email footer
- The preference center

Must describe:
- What data you collect
- Why you collect it
- How long you retain it
- How to request deletion
- Who to contact (data controller details)

---

## Data Retention

| Data | Retention | Action |
|---|---|---|
| Unconfirmed subscribers | 7 days | Auto-delete via scheduled task |
| Unsubscribed subscribers | 30 days after unsubscribe | Anonymize or delete |
| Campaign send data | 12 months | Archive then delete |
| Campaign link clicks | 12 months | Archive then delete |
| Bounced subscribers | Indefinite (suppression list) | Keep email on suppression list, delete PII |

---

## Security Measures

### Form Protection
- CSRF tokens on all forms
- Rate limiting: `throttle:5,1` on subscription endpoint
- Honeypot field to reduce bot submissions

### URL Security
- All unsubscribe/preference URLs use `URL::signedRoute()`
- Signed URLs prevent parameter tampering
- URLs include expiration where appropriate

### Webhook Security
- Verify Elastic Email webhook origin (IP allowlist)
- Process webhooks asynchronously (don't expose processing time)

### Admin Access
- Newsletter CP sections gated behind Statamic roles
- Only authorized users can send campaigns
- Activity logging for audit trail (spatie/laravel-activitylog)

### Data at Rest
- Encrypt sensitive fields if required by jurisdiction
- Use Laravel's `encrypted` cast on model attributes
- MySQL connection over TLS on Cloudways (enabled by default)
