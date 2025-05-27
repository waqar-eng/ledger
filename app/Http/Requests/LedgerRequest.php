<?php

namespace App\Http\Requests;

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
            'amount' => 'required|integer',
            'type' => 'required|in:credit,debit',
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'ledger_type' => 'required|in:sale,expense,purchase', // Add this
        ];
    }
}
