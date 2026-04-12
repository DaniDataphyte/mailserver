<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriberGroup extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description'];

    public function subGroups(): HasMany
    {
        return $this->hasMany(SubscriberSubGroup::class);
    }

    public function activeSubscribersCount(): int
    {
        return $this->subGroups()
            ->withCount(['subscribers' => fn ($q) => $q->where('subscribers.status', 'active')])
            ->get()
            ->sum('subscribers_count');
    }
}
