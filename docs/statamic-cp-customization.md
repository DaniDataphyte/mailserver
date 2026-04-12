# Statamic Control Panel Customization

Custom CP sections for managing newsletters, subscribers, and campaigns within Statamic's admin interface.

---

## CP Navigation

Extend Statamic's sidebar navigation using `Nav::extend()` in a service provider:

```php
// app/Providers/NewsletterServiceProvider.php

Nav::extend(function ($nav) {
    $nav->content('Newsletter')
        ->section('Newsletter')
        ->icon('email')
        ->children([
            $nav->item('Campaigns')->route('newsletter.campaigns.index'),
            $nav->item('Subscribers')->route('newsletter.subscribers.index'),
            $nav->item('Groups')->route('newsletter.groups.index'),
            $nav->item('Templates')->route('newsletter.templates.index'),
            $nav->item('Analytics')->route('newsletter.analytics.index'),
        ]);
});
```

---

## Custom CP Sections

### 1. Campaigns Section

**List View (`/cp/newsletter/campaigns`)**
- Table: Name, Subject, Status, Recipients, Open Rate, Sent Date
- Filters: Status (draft/scheduled/sending/sent)
- Actions: Create, Edit, Duplicate, Delete, Cancel

**Create/Edit View**
- Form fields: Name, Subject, From Name, From Email, Reply-To
- Template selector (dropdown from email_templates)
- Content editor (rich text / HTML)
- Audience picker (checkboxes for groups/sub-groups + "All" option)
- Schedule picker (send now or date/time)
- Preview button (renders email in modal)
- Test send button (sends to admin email)

**Detail View**
- Campaign info summary
- Stats: sent, delivered, opened, clicked, bounced, complained
- Recipient list with individual statuses
- Top clicked links

### 2. Subscribers Section

**List View (`/cp/newsletter/subscribers`)**
- Table: Email, Name, Status, Groups, Subscribed Date
- Filters: Status, Group, Sub-Group
- Search by email/name
- Actions: Add, Edit, Delete (with GDPR erasure), Import CSV, Export CSV

**Detail View**
- Subscriber info
- Group memberships (with toggle)
- Send history (all campaigns sent to this subscriber)

### 3. Groups Section

**List View (`/cp/newsletter/groups`)**
- Table: Group Name, Sub-Groups, Subscriber Count
- Actions: Create, Edit, Delete

**Edit View**
- Group name, description
- Sub-groups management (add/remove/reorder)
- Subscriber count per sub-group

### 4. Templates Section

**List View (`/cp/newsletter/templates`)**
- Table: Name, Description, Default, Last Updated
- Actions: Create, Edit, Duplicate, Delete, Set Default

**Edit View**
- Name, description
- HTML editor with live preview
- Preview in different email clients (if Litmus/Email on Acid integration)

### 5. Analytics Section

**Dashboard (`/cp/newsletter/analytics`)**
- Overall stats: total subscribers, growth trend
- Recent campaigns with open/click rates
- Top performing campaigns
- Subscriber acquisition chart
- Bounce/complaint trend

---

## Dashboard Widget

Add a widget to the Statamic CP dashboard:

```php
// app/Widgets/NewsletterWidget.php

class NewsletterWidget extends Widget
{
    public function html()
    {
        $recentCampaigns = Campaign::latest('sent_at')
            ->where('status', 'sent')
            ->take(5)
            ->get();

        return view('newsletter::widgets.overview', [
            'campaigns' => $recentCampaigns,
            'totalSubscribers' => Subscriber::active()->count(),
        ]);
    }
}
```

---

## CP Views

All CP views use Blade templates with Statamic's CP styles:

```
resources/views/newsletter/
├── campaigns/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── subscribers/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── groups/
│   ├── index.blade.php
│   └── edit.blade.php
├── templates/
│   ├── index.blade.php
│   └── edit.blade.php
├── analytics/
│   └── index.blade.php
└── widgets/
    └── overview.blade.php
```

### Frontend Libraries in CP Views
- **Alpine.js** - interactive audience picker, live preview toggle, form interactions
- **Tailwind CSS** - Statamic CP already uses Tailwind, extend with custom classes as needed

---

## Permissions

Define custom permissions for newsletter features:

```php
// app/Providers/NewsletterServiceProvider.php

Permission::group('newsletter', 'Newsletter', function () {
    Permission::register('view campaigns')->label('View Campaigns');
    Permission::register('create campaigns')->label('Create Campaigns');
    Permission::register('send campaigns')->label('Send Campaigns');
    Permission::register('manage subscribers')->label('Manage Subscribers');
    Permission::register('manage groups')->label('Manage Groups');
    Permission::register('manage templates')->label('Manage Templates');
    Permission::register('view analytics')->label('View Analytics');
});
```

Assign permissions to Statamic user roles as needed.
