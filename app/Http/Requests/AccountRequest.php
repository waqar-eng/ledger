<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('account_id');

        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {

        $id = $this->route('account_id');
        $commonRules = [

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'name')->ignore($id),
            ],

            'description' => [
                'required',
                'string',
                'max:255'
            ],

            'opening_balance' => [
                'nullable',
                'numeric'
            ],

            'is_active' => [
                'nullable',
                'boolean'
            ]
        ];

        $ruleId = [
            'id' => 'required|integer|exists:accounts,id,deleted_at,NULL'
        ];

        switch ($this->method()) {

            case 'POST':
                return $commonRules;

            case 'PUT':
            case 'PATCH':
                return array_merge(
                    $ruleId,
                    $commonRules
                );

            case 'DELETE':
                return $ruleId;

            case 'GET':
                return $ruleId;

            default:
                return [];
        }
    }
}
