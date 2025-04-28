<?php

namespace App\Http\Requests\Ledger;

use Illuminate\Foundation\Http\FormRequest;

class LedgerRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all for now
    }

    public function rules()
    {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|email|unique:users,email',
        ];
    }
}
