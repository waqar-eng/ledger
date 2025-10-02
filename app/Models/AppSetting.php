<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    // Helper for fetching setting
    public static function getValue(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
