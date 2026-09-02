<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessRequest extends FormRequest
{
    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('business_id');

        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        $commonRules = [
            'name' => 'required|string|max:255|unique:businesses,name',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];

        $ruleId = [
            'id' => 'required|integer|exists:businesses,id,deleted_at,NULL'
        ];

        switch ($this->method()) {

            case 'POST':
                return $commonRules;

            case 'DELETE':
                return $ruleId;

            case 'PUT':
            case 'PATCH':
                return array_merge($ruleId, $commonRules);

            case 'GET':
                return $ruleId;

            default:
                return [];
        }
    }
}