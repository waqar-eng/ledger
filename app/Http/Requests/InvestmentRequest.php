<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all for now
    }

    public function rules()
    {
        $userId = $this->input('user_id');
        $postRules = [
            'user_id' => ['required', 'exists:users,id'],
            'type' => [
                'required',
                Rule::in(['opening', 'additional', 'withdrawal']),
                Rule::unique('investments')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)
                                ->where('type', 'opening'); 
                })
            ],
            'amount' => 'required|integer|min:1',
            'date' => 'required|date',
        ];
        switch ($this->method()) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
                return $postRules;
            default:
                return $postRules;
        }
    }
}
