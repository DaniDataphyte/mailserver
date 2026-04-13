# DNS Setup (SPF, DKIM, DMARC)

Add these records at your DNS provider (Cloudflare recommended) before going live.
All three are required for deliverability. Without them, emails will land in spam.

---

## 1. SPF Record

Authorises Elastic Email to send email on behalf of your domain.

| Type | Host | Value | TTL |
|---|---|---|---|
| TXT | `@` (or `yourdomain.com`) | `v=spf1 include:_spf.elasticemail.com ~all` | 3600 |

If you already have an SPF record, merge the include into it — you can only have ONE SPF record per domain:
```
v=spf1 include:_spf.elasticemail.com include:otherprovider.com ~all
```

---

## 2. DKIM Record

Elastic Email signs outgoing emails. Get the DKIM key from:
**elasticemail.com > Settings > Domains > your domain > DKIM**

| Type | Host | Value | TTL |
|---|---|---|---|
| TXT | `api._domainkey.yourdomain.com` | (provided by Elastic Email — long string starting with `v=DKIM1;`) | 3600 |

---

## 3. DMARC Record

Instructs receiving servers what to do when SPF/DKIM fails.
Start with `p=none` (monitor only), move to `p=quarantine` after 2 weeks of clean reports.

| Type | Host | Value | TTL |
|---|---|---|---|
| TXT | `_dmarc.yourdomain.com` | `v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com; pct=100` | 3600 |

**Policy progression:**
1. `p=none` → monitor, no action on failures (start here)
2. `p=quarantine` → failed emails go to spam (move here after reviewing reports)
3. `p=reject` → failed emails are dropped (move here once deliverability is confirmed)

---

## 4. Domain Verification in Elastic Email

1. Log in at elasticemail.com
2. Settings > Domains > Add Domain
3. Enter your sending domain
4. Add the TXT verification record to DNS
5. Click Verify
6. Set as default sending domain (blue star icon)
7. Set up the bounce/return-path domain (follow their wizard)

**Verification takes 15–60 minutes** after DNS records propagate.

---

## 5. Webhook Setup in Elastic Email

Once the domain is verified, set up webhooks so tracking events reach your app:

1. elasticemail.com > Settings > Notifications > Add Notification
2. **URL:** `https://yourdomain.com/webhooks/elastic-email`
3. **Enable events:** Sent, Delivered, Opened, Clicked, Bounced, Unsubscribed, Complained
4. Save

Test the endpoint with the "Send Test" button. It must return `200 OK`.

---

## DNS Propagation Check

After adding records, verify they are live:
```bash
# SPF
dig TXT yourdomain.com +short

# DKIM
dig TXT api._domainkey.yourdomain.com +short

# DMARC
dig TXT _dmarc.yourdomain.com +short
```

Or use: https://mxtoolbox.com/SuperTool.aspx
