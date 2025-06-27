<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
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
 public function rules()
{
    $customerId = $this->route('customer')?->id ?? $this->id;

    $postRules = [
        'name'         => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:20|unique:customers,phone_number,' . $customerId,
        'address'      => 'nullable|string|max:500',
        'email'        => 'nullable|email|max:255|unique:customers,email,' . $customerId,
    ];

    return $postRules; 
}

}
