<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PixTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'txid',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
