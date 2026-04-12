<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_type',
        'transaction_id',
        'to_email',
        'payload',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at')->whereNull('error');
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('error');
    }

    public function markProcessed(): void
    {
        $this->update(['processed_at' => now(), 'error' => null]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['error' => $error]);
    }
}
