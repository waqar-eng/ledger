<?php

namespace App\Http\Requests;

use App\AppEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
             AppEnum::Amount->value => 'required',
            'date' => 'required|date',
            // Conditional validation
            'customer_id'     => [
                'nullable',
                'exists:customers,id',
                'required_unless:ledger_type,withdraw,investment,expense',
            ],
            'user_id'         => [
                'nullable',
                'exists:users,id',
                'required_if:ledger_type,withdraw,investment',
            ],
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','repayment','other'])],
                'payment_type' => ['nullable', Rule::in(['cash', 'loan', 'mix'])],
                'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
                'paid_amount' => 'nullable|numeric|min:0',
                'remaining_amount' => 'nullable|numeric|min:0',
                'rate' => 'nullable|numeric|min:0',
               'quantity' => 'nullable|numeric|min:0',
                'bill_no' => 'required|string|max:255',
        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'customer_id'  => 'nullable|integer|exists:customers,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',


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
}
