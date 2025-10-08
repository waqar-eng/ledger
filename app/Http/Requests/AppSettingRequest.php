<?php

namespace App\Http\Requests;

use App\AppEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AppSettingRequest extends FormRequest
{
     public function all($keys = null){
        $data = parent::all();
        $data['id'] = $this->route('app_setting');
        return $data;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $postRules = [
            'key' => [
                'required',
                Rule::in(['updation_period', 'deletion_period']),
            ],
            'value' => [
                'required',
                Rule::in(['1_day', '1_week', '2_weeks', '1_month', '2_months', '6_months', '1_year']),
            ],
            'user_id'         => 'nullable|integer|exists:users,id'
        ];
        $getRules = [

        ];
        $idRule=['id'  => 'required|integer|exists:app_settings,id'];
        switch ($this->method()) {
            case 'GET':
                return $getRules;
            case 'POST':
                return $postRules;
            case 'PUT':
            case 'PATCH':
                return array_merge($idRule,$postRules);

            default:
                return $getRules;
        }
    }
}
