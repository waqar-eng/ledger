<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
      public function all($keys = null){
        $data = parent::all();
        $data['id'] = $this->route('category');
        $data['season_id'] = $this->route('season_id');
        return $data;
    }

    public function authorize():bool
    {
        return true;
    }
    public function rules()
    {

       $commonRules = [
            'categoryName' => 'required|string|max:255',
            'season_id' => 'required|string|max:255'
        ];
         $ruleId = [
            'id' => 'required|integer|exists:categories,id,deleted_at,NULL'
        ];
        switch($this->method()){
            case 'POST':
                return $commonRules;
            case 'DELETE':
                return $ruleId;
            case 'PUT':
            case 'PATCH':
                return array_merge($ruleId,$commonRules);
            case 'GET':
                // if route contains category id → show
                if ($this->route('category')) {
                    return $ruleId;
                }
                return [];
            default:
                return [];
    }
}
}
