<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
        public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('transaction');
        return $data;
    }


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $postRules = [
            'customer_id' => 'nullable|exists:customers,id,deleted_at,NULL',
            'category_id' => 'nullable|exists:categories,id,deleted_at,NULL',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'type' => 'required|string|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'balance' => 'nullable|numeric',
        ];
        $updateRules = [
            'customer_id' => 'nullable|exists:customers,id,deleted_at,NULL',
            'category_id' => 'nullable|exists:categories,id,deleted_at,NULL',
            'date' => 'sometimes|required|date',
            'description' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:debit,credit',
            'amount' => 'sometimes|required|numeric|min:0',
            'balance' => 'nullable|numeric',
        ];


        $transactionId = [
            'id' => 'required|integer|exists:transactions,id,deleted_at,NULL'
        ];


        switch ($this->method()) {
            case 'POST':
                return $postRules;

            case 'PATCH':
            case 'PUT':
                return array_merge($transactionId, $updateRules);
            case 'GET':
            case 'DELETE':
                return $transactionId;
            default:
                return [];
        }
    }
}
