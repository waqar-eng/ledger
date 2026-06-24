<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseTypeRequest extends FormRequest
{
    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('expense_type');
        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {

        $commonRules = [
            'expenseTypeName' => 'required|string|max:255',
        ];

        $ruleId = [
            // 'id' => 'required|integer|exists:expense_types,id,deleted_at,NULL'
        ];

        switch ($this->method()) {

            case 'POST':
                return $commonRules;

            case 'GET':
            case 'DELETE':
                return $ruleId;

            case 'PUT':
            case 'PATCH':
                return array_merge($ruleId, $commonRules);

            default:
                return [];
        }
    }
}
