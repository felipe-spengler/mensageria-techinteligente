<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $fillable = [
        'api_key_id',
        'user_id',
        'to',
        'message',
        'media_url',
        'status',
        'error_message',
        'sent_at',
        'ip_address',
        'is_free',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function getInstanceAttribute()
    {
        $userId = null;
        if ($this->api_key_id && $this->apiKey) {
            $userId = $this->apiKey->user_id;
        } elseif ($this->user_id) {
            $userId = $this->user_id;
        }

        if ($userId) {
            return \App\Models\WhatsappInstance::where('user_id', $userId)->first();
        }

        return null;
    }
}
