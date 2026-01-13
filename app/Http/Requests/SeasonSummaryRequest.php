<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LedgerSeason;
use Illuminate\Validation\Validator;

class SeasonSummaryRequest extends FormRequest
{
     public function all($keys = null){
        $data = parent::all();
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
            case 'GET':
                return [
                    'season_id'  => 'required|integer|exists:season_summaries,season_id'
                ];

            default:
                return [];
        }
    }
}
