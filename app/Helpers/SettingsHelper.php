<?php

namespace App\Helpers;

use App\Models\Setting;

class SettingsHelper
{
    public static function get($key, $default = null)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}

if (! function_exists('db_setting')) {
    function db_setting($key, $default = null)
    {
        return \App\Helpers\SettingsHelper::get($key, $default);
    }
}
