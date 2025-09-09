<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class Log_activity extends Activity
{
    protected $table = 'activity_log';

     protected $casts = [
        'properties' => 'array',
    ];
    
   public const LOG_CREATED = 'Activity_log created successfully';
   public const LOG_UPDATED = 'Activity_log updated successfully';
   public const LOG_DELETED = 'Activity_log deleted successfully';
}