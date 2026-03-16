<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'message_limit',
        'type',
        'price',
    ];

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}
