<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function getValue(string $key, $default = null)
    {
        return self::where('key', $key)->first()?->value ?? $default;
    }

    public static function setValue(string $key, string $value, string $group = 'system')
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }
}
