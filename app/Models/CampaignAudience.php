<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignAudience extends Model
{
    protected $fillable = [
        'campaign_id', 'targetable_type', 'targetable_id', 'send_to_all',
    ];

    protected $casts = [
        'send_to_all' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}
