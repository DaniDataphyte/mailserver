<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriberSubGroup extends Model
{
    use HasFactory;
    protected $fillable = ['subscriber_group_id', 'name', 'slug', 'description'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SubscriberGroup::class, 'subscriber_group_id');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'subscriber_sub_group')
            ->withPivot(['subscribed_at', 'unsubscribed_at'])
            ->wherePivotNull('unsubscribed_at');
    }

    public function allSubscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'subscriber_sub_group')
            ->withPivot(['subscribed_at', 'unsubscribed_at']);
    }
}
