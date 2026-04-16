<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappInstance extends Model
{
    protected $fillable = [
        'user_id',
        'session_name',
        'phone',
        'status',
        'last_qr',
        'schedule_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
