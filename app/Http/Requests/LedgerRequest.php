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
            'customer_id' => [
                'nullable',
                'exists:customers,id',
                'required_unless:ledger_type,withdraw,investment,expense,moisture_loss',
                function ($attribute, $value, $fail) {
                    if ($value && $this->user_id) {
                        $fail(Ledger::USER_AND_CUSTOMER_ERROR);
                    }
                },
            ],
            'user_id' => [
                'nullable',
                'exists:users,id',
                'required_if:ledger_type,withdraw,investment',
                function ($attribute, $value, $fail) {
                    if ($value && $this->customer_id) {
                        $fail(Ledger::USER_AND_CUSTOMER_ERROR);
                    }
                },
            ],

            'category_id'         => [
                'nullable',
                'exists:categories,id',
                'required_if:ledger_type,moisture_loss,sale,purchase',
            ],
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','receive-payment','payment','moisture_loss','other'])],
            'payment_type' => [
                'required_if:ledger_type,sale,purchase,payment, receive-payment',
                Rule::in(['cash', 'credit', 'partial', null]),
            ],
            'remaining_amount' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $paymentType = request('payment_type');
                    $ledger_type = request('ledger_type');
            
                    if (in_array($paymentType, ['credit', 'partial']) && $value <= 0) {
                        $fail('The '.$attribute.' must be greater than 0 for credit or partial payments.');
                    }
            
                    if ($paymentType === 'cash' && $ledger_type!=='receive-payment' && $ledger_type!=='payment' && !in_array($value, [null, 0, '0', '0.00'], true)) {
                        $fail('The '.$attribute.' must be 0 or empty when payment type is cash.');
                    }
                },
            ],
                'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
                'paid_amount' => 'nullable|numeric|min:0',
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
    protected function passedValidation()
    {
        // Only run on create or update
        if (!in_array($this->method(), ['POST', 'PUT', 'PATCH'])) {
            return;
        }

        try {
            LedgerService::ledgerNewTotalAndType($this->all(), $this->id);
        } catch (ValidationException $e) {
            // rethrow to stop validation and send proper error response
            throw $e;
        }
    }
}
