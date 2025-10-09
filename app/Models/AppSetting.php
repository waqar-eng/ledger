<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AppSetting extends Model
{
    use LogsActivity;
    protected $fillable = ['key', 'value','user_id'];
    protected $hidden = ['updated_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('app_settings')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "App setting has been {$eventName}");
    }

    // Helper for fetching setting
    public static function getValue(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public const APP_SETTING_CREATED= "App setting created successfully";
    public const APP_SETTING_UPDATED= "App setting updated successfully";
    public const APP_SETTING_ERROR= "App setting error";
}
