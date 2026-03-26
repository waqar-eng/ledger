<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all users for now; you can add permission checks here
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get the role ID from the route (for updates)
        $roleId = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique name, ignore current role on update
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
        ];
    }

    /**
     * Custom messages (optional)
     */
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