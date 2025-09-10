<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function all($keys = null) {
        $data = parent::all( $keys);
        $data['user_id'] = $this->route('user_id');
        return $data;
     }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    switch ($this->method()) {
        case 'POST':
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ];
        case 'GET':
            return [
                'search' => 'nullable|string',
                'per_page' => 'nullable|integer',
            ];
        case 'PUT':
        case 'PATCH':
            $userId = $this->route('user_id');

            return [
                'name' => 'required|string|max:255',
                'email' => 'required|email|exists:users,email',
                'password' => 'nullable|string|min:6',
            ];
        default:
            return [];
    }
  }   
}
