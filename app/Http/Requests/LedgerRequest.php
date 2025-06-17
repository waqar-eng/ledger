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
        $postRules = [
            'description' => 'required|string|max:255',
            'amount' => 'required|integer',
            'type' => 'required|in:credit,debit',
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'ledger_type' => 'required|in:sale,expense,purchase', // Add this
        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'customer_id'  => 'nullable|integer|exists:customers,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',
            'ledger_type' => 'nullable|in:sale,expense,purchase', // Add this
            'type' => 'nullable|in:credit,debit',
        ];
        switch ($this->method()) {
            case 'GET':
                return $getRules;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                return $postRules;
            default:
                return $getRules;
        }
    }
    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $ledgerType = $this->input('ledger_type');

            if ($ledgerType == 'purchase' && $type !== 'debit') {
                $validator->errors()->add('type', 'Purchase should be debit');
            }
            if ($ledgerType == 'sale' && $type !== 'credit') {
                $validator->errors()->add('type', 'Sale should be credit');
            }
            if ($ledgerType == 'expense' && $type !== 'debit') {
                $validator->errors()->add('type', 'Expense should be debit');
            }
        });
    }
}
