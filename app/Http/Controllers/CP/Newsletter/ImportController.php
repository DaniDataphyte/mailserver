<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function form()
    {
        $subGroups = SubscriberSubGroup::with('group')->orderBy('subscriber_group_id')->get();

        return view('newsletter.cp.subscribers.import', compact('subGroups'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file'          => 'required|file|mimes:csv,txt|max:10240',
            'default_sub_groups' => 'nullable|array',
            'default_sub_groups.*' => 'exists:subscriber_sub_groups,id',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row — normalise to snake_case and resolve aliases
        $rawHeaders = array_map('trim', fgetcsv($handle));
        $headers    = array_map([$this, 'normaliseHeader'], $rawHeaders);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        // Resolve available sub-group slugs for mapping
        $subGroupMap = SubscriberSubGroup::pluck('id', 'slug')->toArray();

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) < 1 || empty(trim($data[0]))) {
                continue;
            }

            $rowData = array_combine(
                $headers,
                array_pad($data, count($headers), null)
            );

            $email = strtolower(trim($rowData['email'] ?? ''));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$row}: Invalid email — {$email}";
                $skipped++;
                continue;
            }

            // Resolve sub-groups from CSV column or fall back to defaults
            $subGroupIds = [];

            if (! empty($rowData['sub_groups'])) {
                $slugs = array_map('trim', explode(',', $rowData['sub_groups']));
                foreach ($slugs as $slug) {
                    if (isset($subGroupMap[$slug])) {
                        $subGroupIds[] = $subGroupMap[$slug];
                    } else {
                        $errors[] = "Row {$row}: Unknown sub-group slug '{$slug}' — skipped for {$email}";
                    }
                }
            }

            // Merge with any defaults selected in the form
            if ($request->filled('default_sub_groups')) {
                $subGroupIds = array_unique(
                    array_merge($subGroupIds, $request->default_sub_groups)
                );
            }

            if (empty($subGroupIds)) {
                $errors[] = "Row {$row}: No valid sub-groups for {$email} — skipped";
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($email, $rowData, $subGroupIds) {
                    $subscriber = Subscriber::firstOrCreate(
                        ['email' => $email],
                        [
                            'first_name'         => trim($rowData['first_name'] ?? ''),
                            'last_name'          => trim($rowData['last_name'] ?? ''),
                            'status'             => 'active',
                            'confirmation_token' => Str::uuid()->toString(),
                        ]
                    );

                    // Backfill token for existing subscribers that were imported without one
                    if (! $subscriber->confirmation_token) {
                        $subscriber->update(['confirmation_token' => Str::uuid()->toString()]);
                    }

                    // Only attach sub-groups not already attached
                    $existing = $subscriber->allSubGroups()
                        ->pluck('subscriber_sub_groups.id')
                        ->toArray();

                    $toAttach = array_diff($subGroupIds, $existing);

                    if ($toAttach) {
                        $subscriber->subGroups()->attach(
                            $toAttach,
                            ['subscribed_at' => now()]
                        );
                    }
                });

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: Failed to import {$email} — {$e->getMessage()}";
                $skipped++;
            }
        }

        fclose($handle);

        return redirect()
            ->route('statamic.cp.newsletter.subscribers.index')
            ->with('import_result', [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ]);
    }

    public function export(Request $request)
    {
        $query = Subscriber::with('subGroups.group')->orderBy('email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sub_group')) {
            $query->whereHas('subGroups', fn ($q) =>
                $q->where('subscriber_sub_groups.id', $request->sub_group)
            );
        }

        $subscribers = $query->get();

        $filename = 'subscribers-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($subscribers) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['email', 'first_name', 'last_name', 'status', 'sub_groups', 'subscribed_at']);

            foreach ($subscribers as $subscriber) {
                $subGroupSlugs = $subscriber->subGroups->pluck('slug')->implode(',');

                fputcsv($handle, [
                    $subscriber->email,
                    $subscriber->first_name,
                    $subscriber->last_name,
                    $subscriber->status,
                    $subGroupSlugs,
                    $subscriber->created_at->toDateString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Normalise a CSV header to snake_case and resolve common aliases.
     * e.g. "Email Address" → "email", "First Name" → "first_name"
     */
    private function normaliseHeader(string $header): string
    {
        $aliases = [
            'email address'  => 'email',
            'email_address'  => 'email',
            'e-mail'         => 'email',
            'first name'     => 'first_name',
            'firstname'      => 'first_name',
            'last name'      => 'last_name',
            'lastname'       => 'last_name',
            'surname'        => 'last_name',
            'sub groups'     => 'sub_groups',
            'subgroups'      => 'sub_groups',
            'group'          => 'sub_groups',
            'groups'         => 'sub_groups',
        ];

        $normalised = strtolower(trim($header));
        $normalised = preg_replace('/\s+/', ' ', $normalised); // collapse spaces

        return $aliases[$normalised] ?? str_replace(' ', '_', $normalised);
    }
}
