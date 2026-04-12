<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignSend extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id', 'subscriber_id', 'status',
        'elastic_email_transaction_id',
        'sent_at', 'delivered_at', 'opened_at', 'clicked_at',
        'bounced_at', 'failed_at', 'bounce_reason',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at'    => 'datetime',
        'clicked_at'   => 'datetime',
        'bounced_at'   => 'datetime',
        'failed_at'    => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function linkClicks(): HasMany
    {
        return $this->hasMany(CampaignLinkClick::class);
    }
}
