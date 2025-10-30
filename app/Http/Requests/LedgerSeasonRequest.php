<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LedgerSeason;
use Illuminate\Validation\Validator;

class LedgerSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $excludeId = $this->route('ledger_season');

        $rules = [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:upcoming,active,completed',
        ];

        // start_date validation
        if ($excludeId) {
            // Update: allow past date
            $rules['start_date'] = 'required|date';
        } else {
            // Create: only today or future
            $rules['start_date'] = 'required|date|after_or_equal:today';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $start = $this->start_date;
            $end   = $this->end_date;
            $excludeId = $this->route('ledger_season'); // id of the season being edited

            // Only perform overlap check if end_date is provided
            if ($end) {
                $exists = LedgerSeason::where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_date', [$start, $end])
                          ->orWhereBetween('end_date', [$start, $end])
                          ->orWhere(function ($q) use ($start, $end) {
                              $q->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                          });
                })
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists();

                if ($exists) {
                    $validator->errors()->add('start_date', 'Another season already exists in this date range.');
                }
            }

            // Only one active season allowed at a time
            if ($this->status === 'active') {
                $activeExists = LedgerSeason::where('status', 'active')
                    ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                    ->exists();

                if ($activeExists) {
                    $validator->errors()->add('status', 'Another active season already exists.');
                }
            }
        });
    }
}
