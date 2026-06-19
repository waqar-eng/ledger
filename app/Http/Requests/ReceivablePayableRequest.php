<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivablePayableRequest extends FormRequest
{
    public function all($keys = null)
    {
        $data = parent::all($keys);
        $data['season_id'] = $this->route('season_id');
        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'season_id' => 'required|exists:ledger_seasons,id',
        ];
        
    }

}