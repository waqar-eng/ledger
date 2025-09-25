<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use HasFactory, LogsActivity ,SoftDeletes;
    protected $fillable = ['categoryName'];



public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->useLogName('category')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "Category has been {$eventName}");
}

    public const CATEGORY_CREATED = "Category created successfully";
    public const CATEGORY_UPDATED = "Category updated successfully";
    public const CATEGORY_DELETED = "Category deleted successfully";
    public const CATEGORYS_FETCHED  = "All categories fetched successfully";
    public const CATEGORY_FETCHED   = "Category fetched successfully.";


}
