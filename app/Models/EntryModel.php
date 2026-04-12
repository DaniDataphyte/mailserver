<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Statamic\Eloquent\Entries\EntryModel as BaseEntryModel;

/**
 * Custom EntryModel that adds UUID auto-generation on insert.
 *
 * Statamic's Eloquent driver stores entries with a char(36) UUID primary key.
 * The core driver does not auto-generate the UUID — it relies on the entry
 * object already having an ID before save(). When creating a brand-new entry
 * via the CP (Entry::make() with no explicit id()), the id is null and MySQL
 * throws "Field 'id' doesn't have a default value".
 *
 * Laravel's HasUuids trait hooks into the Eloquent `creating` event and
 * generates a UUID for the primary key before the INSERT fires, solving
 * this transparently without touching vendor code.
 *
 * Registered in config/statamic/eloquent-driver.php:
 *   'model' => \App\Models\EntryModel::class
 */
class EntryModel extends BaseEntryModel
{
    use HasUuids;
}
