<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    use HasFactory;
    protected $fillable = [
        'email', 'first_name', 'last_name', 'status',
        'confirmation_token', 'confirmed_at', 'unsubscribed_at',
        'ip_address', 'user_agent', 'metadata',
    ];

    protected $casts = [
        'confirmed_at'    => 'datetime',
        'unsubscribed_at' => 'datetime',
        'metadata'        => 'array',
    ];

    public function subGroups(): BelongsToMany
    {
        return $this->belongsToMany(SubscriberSubGroup::class, 'subscriber_sub_group')
            ->withPivot(['subscribed_at', 'unsubscribed_at'])
            ->wherePivotNull('unsubscribed_at');
    }

    public function allSubGroups(): BelongsToMany
    {
        return $this->belongsToMany(SubscriberSubGroup::class, 'subscriber_sub_group')
            ->withPivot(['subscribed_at', 'unsubscribed_at']);
    }

    public function campaignSends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->email;
    }

    public function sendStats(): array
    {
        return $this->campaignSends()
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(status IN ("delivered","opened","clicked")) as total_delivered,
                SUM(status IN ("failed","bounced")) as total_failed,
                SUM(opened_at IS NOT NULL) as total_opened
            ')
            ->first()
            ->toArray();
    }
}
