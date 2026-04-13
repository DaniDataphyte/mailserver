<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'entry_id', 'collection', 'name', 'subject',
        'from_name', 'from_email', 'reply_to',
        'status', 'scheduled_at', 'sent_at',
        'total_recipients', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function audiences(): HasMany
    {
        return $this->hasMany(CampaignAudience::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Resolve the from address and name for this campaign
     * based on its collection, falling back to config defaults.
     */
    public function sender(): array
    {
        $collection = $this->collection ?? 'fallback';

        $sender = config(
            "newsletter.collections.{$collection}",
            config('newsletter.fallback')
        );

        // Per-campaign overrides stored on the model take precedence
        return [
            'from_email' => $this->from_email ?: $sender['from_email'],
            'from_name'  => $this->from_name  ?: $sender['from_name'],
            'reply_to'   => $this->reply_to   ?: $sender['reply_to'],
        ];
    }

    public function stats(): array
    {
        return $this->sends()
            ->selectRaw('
                COUNT(*) as total_recipients,
                SUM(status IN ("sent","delivered","opened","clicked")) as total_sent,
                SUM(status = "queued") as total_queued,
                SUM(status IN ("delivered","opened","clicked")) as total_delivered,
                SUM(status IN ("failed","bounced","complained")) as total_failed,
                SUM(opened_at IS NOT NULL) as total_opened,
                SUM(status IN ("delivered","opened","clicked") AND opened_at IS NULL) as total_unread
            ')
            ->first()
            ->toArray();
    }
}
