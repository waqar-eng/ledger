<?php

namespace App\Http\Requests;

use App\AppEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class LedgerRequest extends FormRequest
{
     public function all($keys = null){
        $data = parent::all();
        $data['id'] = $this->route('ledger');
        return $data;
    }

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
                'required_unless:ledger_type,withdraw,investment,expense,moisture_loss',
            ],
            'user_id'         => [
                'nullable',
                'exists:users,id',
                'required_if:ledger_type,withdraw,investment',
            ],
            'category_id'         => [
                'nullable',
                'exists:categories,id',
                'required_if:ledger_type,moisture_loss,sale,purchase',
            ],
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','repayment','moisture_loss','other'])],
            'payment_type' => [
                'required_if:ledger_type,sale,purchase',
                Rule::in(['cash', 'credit', 'partial']),
            ],
            'remaining_amount' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $paymentType = request('payment_type');
            
                    if (in_array($paymentType, ['credit', 'partial']) && $value <= 0) {
                        $fail('The '.$attribute.' must be greater than 0 for credit or partial payments.');
                    }
            
                    if ($paymentType === 'cash' && !in_array($value, [null, 0, '0', '0.00'], true)) {
                        $fail('The '.$attribute.' must be 0 or empty when payment type is cash.');
                    }
                },
            ],
                'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
                'paid_amount' => ['required_if:ledger_type,sale,purchase','numeric','min:0'],
                'rate' => ['required_if:ledger_type,sale,purchase','numeric'],
                'quantity' => ['required_if:ledger_type,sale,purchase','numeric'],
                'bill_no' => 'required|string|max:255',
        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'customer_id'  => 'nullable|integer|exists:customers,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',


        ];
        $idRule=['id'  => 'required|integer|exists:ledgers,id'];
        switch ($this->method()) {
            case 'GET':
                return $getRules;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                return $postRules;
            case 'DELETE':
                return $idRule;
            default:
                return $getRules;
        }
    }
}
