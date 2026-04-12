# Subscriber Management

---

## How Subscribers Enter the System

Subscribers are **imported by admins**, not self-subscribed via a public form. Consent was collected on the source websites (Dataphyte Insight or Foundation) before import.

| Source | Group | Sub-groups |
|---|---|---|
| Dataphyte Insight website | Insight Subscribers | Whatever verticals they selected (Topics, Marina & Maitama, SenorRita, etc.) |
| Foundation website | Foundation | Whatever options they selected (Weekly, Activities, etc.) |

Groups and sub-groups are not hardcoded — new ones can be created in the CP at any time.

---

## Features

### Subscriber-Facing (in emails)
- Unsubscribe via signed URL (one-click, removes from all lists)
- Manage preferences via signed URL (toggle sub-group memberships)
- View in browser link

### Admin (Statamic CP)
- Subscriber list with filters (group, sub-group, status)
- Add/edit individual subscribers manually
- **CSV import** (primary workflow — from source websites)
- CSV export
- View subscriber send history
- Manage groups and sub-groups

---

## Import Flow

1. Admin downloads export from source website
2. Maps CSV columns to system fields (email, first_name, last_name, sub_groups)
3. Uploads CSV in CP
4. System validates rows, skips duplicates
5. Imports with `status = active` (consent already collected at source)
6. Assigns to correct sub-groups based on CSV data
7. Import report: imported count, skipped, errors

### Import CSV Format
```
email,first_name,last_name,sub_groups
john@example.com,John,Doe,"topics,senorrita"
jane@example.com,Jane,Smith,"weekly"
```

`sub_groups` is a comma-separated list of sub-group slugs.

---

## Opt-Out Flow

### One-Click Unsubscribe (RFC 8058)
Add headers to every outgoing email:
```
List-Unsubscribe: <https://yourdomain.com/unsubscribe/{signed-token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

### Unsubscribe Page
- Signed URL prevents tampering
- Option 1: Unsubscribe from specific sub-group
- Option 2: Unsubscribe from all (global unsubscribe)
- Sets `subscriber.status = unsubscribed` and `subscriber.unsubscribed_at = now()`
- Updates pivot table `unsubscribed_at` for relevant sub-groups

---

## Preference Center

Public page where subscribers manage their subscriptions.

### Access
Linked from every email footer. Accessed via signed URL:
```
https://yourdomain.com/preferences/{signed-token}
```

### Features
- Toggle sub-group memberships on/off
- View which groups/sub-groups they're subscribed to
- Update name/email
- Global unsubscribe option

### Frontend
- Alpine.js for toggling sub-group checkboxes
- Tailwind CSS for styling
- CSRF-protected form submission

---

## CSV Import/Export

### Import
- Upload CSV with columns: email, first_name, last_name, sub_groups
- Validate each row, skip duplicates
- Queue the import job for large files
- Report: imported count, skipped count, error details

### Export
- Filter by group/sub-group/status
- Columns: email, first_name, last_name, status, groups, subscribed_at
- Queue for large datasets, provide download link when ready

### Package
```
composer require maatwebsite/excel
```

---

## Subscriber Statuses

| Status | Description | Can Receive Email? |
|---|---|---|
| pending | Awaiting confirmation | No |
| active | Confirmed and subscribed | Yes |
| unsubscribed | User opted out | No |
| bounced | Hard bounce detected | No |
| complained | Marked as spam | No |

Only `active` subscribers are included in campaign sends. Status transitions are one-way (bounced/complained cannot be reactivated without manual admin action).
