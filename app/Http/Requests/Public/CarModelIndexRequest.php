<?php

namespace App\Http\Requests\Public;

use App\Enums\PowertrainType;
use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarModelIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'search' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', Rule::enum(VehicleType::class)],
            'powertrain' => ['nullable', Rule::enum(PowertrainType::class)],
            // La prima Ferrari, la 125 S, è del 1947
            'year_from' => ['nullable', 'integer', 'min:1947', 'max:2100'],
            'year_to' => [
                'nullable',
                'integer',
                'min:1947',
                'max:2100',
                Rule::when($this->filled('year_from'), ['gte:year_from']),
            ],
            'sort' => ['nullable', Rule::in(['name', '-name', 'year', '-year'])],
        ];
    }
}
