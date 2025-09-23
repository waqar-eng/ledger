<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = ['categoryName'];

    public const CATEGORY_CREATED = "Category created successfully";
    public const CATEGORY_UPDATED = "Category updated successfully";
    public const CATEGORY_DELETED = "Category deleted successfully";
    public const CATEGORYS_FETCHED  = "All categories fetched successfully";
    public const CATEGORY_FETCHED   = "Category fetched successfully.";



}
