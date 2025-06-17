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
            AppEnum::Amount->value => 'required|integer',
            'type' => ['required' ,Rule::in([AppEnum::Credit->value , AppEnum::Debit->value])],
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'ledger_type' => ['required' , Rule::in([AppEnum::Sale, AppEnum::Expense, AppEnum::Purchase])]
        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'customer_id'  => 'nullable|integer|exists:customers,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',
           'ledger_type' => ['required' , Rule::in([AppEnum::Sale, AppEnum::Expense, AppEnum::Purchase])],
            'type' => ['required' ,Rule::in([AppEnum::Credit->value , AppEnum::Debit->value])]

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

            if ($ledgerType == AppEnum::Purchase && $type !== AppEnum::Debit) {
                $validator->errors()->add('type', 'Purchase should be debit');
            }
            if ($ledgerType == AppEnum::Sale && $type !== AppEnum::Credit) {
                $validator->errors()->add('type', 'Sale should be credit');
            }
            if ($ledgerType == AppEnum::Expense && $type !== AppEnum::Debit) {
                $validator->errors()->add('type', 'Expense should be debit');
            }
        });
    }
}
