<?php

namespace App\Services\Newsletter;

use Statamic\Entries\Entry;

/**
 * Resolves the Blade view key for a given Statamic entry.
 *
 * Priority order:
 *  1. Stored `template` field on the entry  (set by blueprint hidden-field default on save)
 *  2. Convention: emails.{collection}.{blueprint_handle}
 *  3. Collection fallback: emails.{collection}.default
 *  4. Hard fallback: emails.layout
 */
class TemplateResolver
{
    public function resolve(?object $entry, ?string $collection = null): string
    {
        if (! $entry) {
            return 'emails.layout';
        }

        // 1. Stored field — set automatically by blueprint default on first save
        $stored = $entry->get('template');
        if ($stored && view()->exists($stored)) {
            return $stored;
        }

        // 2. Convention — collection/blueprint_handle must both be available
        $col       = $entry->collectionHandle()  ?? $collection;
        $blueprint = $entry->blueprint()?->handle();

        if ($col && $blueprint) {
            $convention = "emails.{$col}.{$blueprint}";
            // Normalise underscores to hyphens so blueprint handles match file names
            $convention = str_replace('_', '-', $convention);
            if (view()->exists($convention)) {
                return $convention;
            }
        }

        // 3. Collection default
        if ($col) {
            $colDefault = "emails.{$col}.default";
            if (view()->exists($colDefault)) {
                return $colDefault;
            }
        }

        // 4. Hard fallback
        return 'emails.layout';
    }
}
