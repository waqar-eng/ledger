<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterAccountTransferRequest extends FormRequest
{
     public function all($keys = null){
        $data = parent::all();
        $data['ledger_season_id'] = $this->route('season_id');
        return $data;
    }

    public function authorize()
    {
        return true; // Allow all for now
    }

    public function rules()
    {
        return [
            'ledger_season_id' => 'required|exists:ledger_seasons,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'ledger_type' => [ 'required', Rule::in(['inter_account_transfer'])],
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => 'required|string|max:255',
        ];
    }
}
