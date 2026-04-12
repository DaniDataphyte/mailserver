<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'blade_view', 'collection', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
