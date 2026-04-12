<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLinkClick extends Model
{
    protected $fillable = [
        'campaign_send_id', 'url', 'clicked_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function campaignSend(): BelongsTo
    {
        return $this->belongsTo(CampaignSend::class);
    }
}
