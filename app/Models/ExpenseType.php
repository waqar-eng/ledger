<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['expenseTypeName'];

    protected $hidden = ['deleted_at', 'updated_at'];

    public const EXPENSE_TYPE_CREATED = "Expense type created successfully";
    public const EXPENSE_TYPE_UPDATED = "Expense type updated successfully";
    public const EXPENSE_TYPE_DELETED = "Expense type deleted successfully";
    public const EXPENSE_TYPES_FETCHED = "All expense types fetched successfully";
    public const EXPENSE_TYPE_FETCHED = "Expense type fetched successfully";
}