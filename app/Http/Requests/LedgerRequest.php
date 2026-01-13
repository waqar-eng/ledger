<?php

namespace App\Http\Requests;

use App\AppEnum;
use App\Models\Ledger;
use App\Services\LedgerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
             AppEnum::Amount->value => [
                'nullable',
                'required_unless:ledger_type,purchase',
            ],
            'date' => 'required|date',
            // Conditional validation
            'user_id' => [
                'nullable',
                'exists:users,id',
                'required_unless:ledger_type,withdraw,investment,expense,moisture_loss',
            ],

            'category_id'         => [
                'nullable',
                'exists:categories,id',
                'required_if:ledger_type,moisture_loss,sale,purchase',
            ],
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','receive-payment','payment','moisture_loss','other'])],
            'payment_type' => [
                 'nullable',
                Rule::in(['cash', 'credit', 'partial', null]),
            ],
            'remaining_amount' => [
                'nullable',
                'numeric',
            ],
                'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
                'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
                    function ($attribute, $value, $fail) {
                        $amount = (float) request('amount');              // total bill amount
                        $remaining = (float) request('remaining_amount'); // remaining amount
                        $paymentType = request('payment_type');

                        // Prevent overpayment more than total
                        if ($value > $amount) {
                            $fail('The '.$attribute.' cannot be greater than the total amount ('.$amount.').');
                        }
                        // Prevent overpayment beyond remaining for credit or partial
                        if (in_array($paymentType, ['credit', 'partial']) && $value > $remaining) {
                            $fail('The '.$attribute.' cannot be greater than the remaining amount ('.$remaining.').');
                        }

                    },
                ],

                'rate' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|numeric|min:0',
                'bill_no' => 'required|string|max:255',
        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'user_id'  => 'nullable|integer|exists:users,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',


        ];
        $idRule=['id'  => 'required|integer|exists:ledgers,id'];
        $updateRule = [
            'description' => 'required|string|max:255',
             AppEnum::Amount->value => [
                'nullable',
                'required_unless:ledger_type,purchase',
            ],
            'date' => 'required|date',
            // Conditional validation
            'user_id' => [
                'nullable',
                'exists:users,id',
                'required_unless:ledger_type,withdraw,investment,expense,moisture_loss',
            ],

            'category_id'         => [
                'nullable',
                'exists:categories,id',
                'required_if:ledger_type,moisture_loss,sale,purchase',
            ],
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','receive-payment','payment','moisture_loss','other'])],
            'payment_type' => [
                 'nullable',
                Rule::in(['cash', 'credit', 'partial', null]),
            ],
            'remaining_amount' => [
                'nullable',
                'numeric',
            ],
                'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
                'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
                    function ($attribute, $value, $fail) {
                        $amount = (float) request('amount');              // total bill amount
                        $remaining = (float) request('remaining_amount'); // remaining amount
                        $paymentType = request('payment_type');           // cash / credit / partial
                        // Prevent overpayment more than total
                        if ($value > $amount) {
                            $fail('The '.$attribute.' cannot be greater than the total amount ('.$amount.').');
                        }
                        // Prevent overpayment beyond remaining for credit or partial
                        if (in_array($paymentType, ['credit', 'partial']) && $value > $remaining) {
                           // $fail('The '.$attribute.' cannot be greater than the remaining amount ('.$remaining.').');
                        }
                    },
                ],
            'rate' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'bill_no' => 'required|string|max:255',
        ];

        switch ($this->method()) {
            case 'GET':
                return $getRules;
            case 'POST':
                return $postRules;
            case 'PUT':
            case 'PATCH':
                return $updateRule;
            case 'DELETE':
                return $idRule;
            default:
                return $getRules;
        }
    }
}
