<?php

if (!function_exists('settings')) {
    function settings($key = null, $default = null)
    {
        if ($key === null) {
            return \App\Models\SystemSetting::pluck('value', 'key');
        }

        $setting = \App\Models\SystemSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
