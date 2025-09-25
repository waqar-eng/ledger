<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
      public function all($keys = null){
        $data = parent::all();
        $data['id'] = $this->route('customer');
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
 public function rules()
{

   $postRules = [
        'name' => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:20|unique:customers,phone_number',
        'address' => 'nullable|string|max:500',
        'email' => 'nullable|email|max:255|unique:customers,email',
    ];

    $updateRules = [
         'name' => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:20|exists:customers,phone_number',
        'address' => 'nullable|string|max:500',
        'email' => 'nullable|email|max:255|exists:customers,email',
    ];

    $customerId = [
        'id' => 'required|integer|exists:customers,id,deleted_at,NULL'
    ];

     switch ($this->method())
     {
        case 'POST':
            return $postRules;
        case 'PATCH':
        case 'PUT':
            return array_merge($customerId, $updateRules);
        case 'DELETE':
            return $customerId;
        case 'GET':

        default:
            return [];
     }
}

}
