<?php

namespace App\Http\Requests;
use App\Models\Ledger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LedgerRequest extends FormRequest
{
     public function all($keys = null){
        $data = parent::all();
        $data['id'] = $this->route('ledger_id');
        $data['season_id'] = $this->route('season_id');
        return $data;
    }

    public function authorize()
    {
        return true; // Allow all for now
    }

    public function rules()
    {
        $commonPostNupdate=[
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'expense_type_id' => ['nullable', 'integer', 'required_if:ledger_type,expense', 'exists:expense_types,id'],
            'category_id' => 'required|exists:categories,id',
            'ledger_type' => [ 'required', Rule::in(['sale','purchase','expense','investment','withdraw','receive-payment','payment','moisture_loss','other'])],
            'rate' => ['nullable','numeric', 'min:0', 'required_if:ledger_type,sale,purchase,moisture_loss',],
            'quantity' => ['nullable','numeric', 'min:0', 'required_if:ledger_type,sale,purchase,moisture_loss',],
            'bill_no' => 'required|string|max:255',
            'remaining_amount' => ['nullable','numeric','min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0','required_if:ledger_type,sale,purchase,receive-payment,payment',
                function ($attribute, $value, $fail) {
                    $amount = (float) request('amount');              // total bill amount
                    $remaining = (float) request('remaining_amount'); // remaining amount
                    // Prevent overpayment more than total
                    if ($value > $amount) {
                        $fail('The '.$attribute.' cannot be greater than the total amount ('.$amount.').');
                    }
                    // Prevent overpayment beyond remaining
                    if ($remaining > $amount) {
                        $fail('The '.$attribute.' cannot be greater than the amount ('.$amount.').');
                    }
                },
            ],
            'user_id' => [
            'nullable',
            'exists:users,id',
            'required_unless:ledger_type,expense,moisture_loss',
                function ($attribute, $value, $fail) {
                    $ledgerType = request('ledger_type');
                    $remaining  = (float) request('remaining_amount');
                    if (! $value || ! $ledgerType) {
                        return;
                    }
                    $user = \App\Models\User::find($value);
                    if (! $user) {
                        return;
                    }
                    // Purchase / Payment → Supplier
                    if (in_array($ledgerType, ['purchase', 'payment'])) {
                        if ($user->type !== 'supplier') {
                            $fail("For {$ledgerType}, the user must be a supplier. Selected: {$user->type}.");
                        }
                        // Block walk-in supplier ONLY if remaining exists
                        if ($remaining > 0 && (int) $user->id === 4) {
                            $fail(Ledger::WALK_IN_SUPPLIER_ACCOUNT_ERROR);
                        }
                    }

                    // Sale / Receive-payment → Buyer
                    if (in_array($ledgerType, ['sale', 'receive-payment'])) {
                        if ($user->type !== 'buyer') {
                            $fail("For {$ledgerType}, the user must be a buyer. Selected: {$user->type}.");
                        }
                        // Block walk-in buyer ONLY if remaining exists
                        if ($remaining > 0 && (int) $user->id === 3) {
                            $fail(Ledger::WALK_IN_BUYER_ACCOUNT_ERROR);
                        }
                    }
                    // Investment/withdraw → Investor or owner
                    if (in_array($ledgerType, ['withdraw', 'investment'])) {
                        if (!in_array($user->type, ['investor', 'owner'])) {
                            $fail("For {$ledgerType}, the user must be a investor. Selected: {$user->type}.");
                        }
                    }
                },
            ],

        ];
        $getRules = [
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date'   => 'nullable|date|required_with:start_date|after_or_equal:start_date',
            'user_id'  => 'nullable|integer|exists:users,id',
            'search_term'  => 'nullable|string',
            'per_page'  => 'nullable|integer',
        ];
        $idRule=['id'  => 'required|integer|exists:ledgers,id'];

        switch ($this->method()) {
            case 'GET':
                return $getRules;
            case 'POST':
                return $commonPostNupdate;
            case 'PUT':
            case 'PATCH':
                return array_merge($commonPostNupdate, $idRule);
            case 'DELETE':
                return $idRule;
            default:
                return $getRules;
        }
    }
}
