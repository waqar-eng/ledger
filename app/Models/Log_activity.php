<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class Log_activity extends Activity
{
    use SoftDeletes;
    protected $table = 'activity_log';

     protected $casts = [
        'properties' => 'array',
    ];
    
   public const LOG_CREATED = 'Activity_log created successfully';
   public const LOG_UPDATED = 'Activity_log updated successfully';
   public const LOG_DELETED = 'Activity_log deleted successfully';
}