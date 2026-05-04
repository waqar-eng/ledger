<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function all($keys = null)
    {
        $data = parent::all($keys);
        $data['role_id'] = $this->route('role_id');
        $data['season_id'] = $this->route('season_id');
        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        switch ($this->method()) {

            case 'POST':
                return [
                    'name' => 'required|string|max:255|unique:roles,name',
                ];

            case 'GET':
                return [
                    'search' => 'nullable|string',
                    'per_page' => 'nullable|integer',
                ];

            case 'PUT':
            case 'PATCH':
                $roleId = $this->route('role_id');

                return [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('roles', 'name')->ignore($roleId),
                    ],
                ];

            default:
                return [];
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'name.unique' => 'This role name already exists',
            'name.string' => 'Role name must be a valid string',
            'name.max' => 'Role name must not exceed 255 characters',
        ];
    }
}