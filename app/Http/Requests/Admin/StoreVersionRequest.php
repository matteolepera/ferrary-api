<?php

namespace App\Http\Requests\Admin;

use App\Enums\BodyType;
use App\Enums\ProductionType;
use App\Enums\VehicleType;
use App\Http\Requests\Admin\Concerns\VersionDetailsRules;
use App\Models\Version;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreVersionRequest extends FormRequest
{
    use VersionDetailsRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', Version::class);
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', new Slug, 'unique:versions,slug'],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'body_type' => [
                'nullable',
                Rule::enum(BodyType::class),
                // Una F1 può essere solo monoposto
                Rule::when($this->input('vehicle_type') === VehicleType::F1->value, [Rule::in([BodyType::Monoposto->value])]),
            ],
            'project_code' => ['nullable', 'string', 'max:30'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:9'],
            'year_start' => ['required', 'integer', 'min:1947', 'max:2100'],
            'year_end' => ['nullable', 'integer', 'gte:year_start', 'max:2100', 'prohibited_if:in_production,true'],
            'in_production' => ['sometimes', 'boolean'],
            'production_type' => ['nullable', Rule::enum(ProductionType::class)],
            'units_produced' => [
                'nullable',
                'integer',
                'min:1',
                // Un one-off è un esemplare unico
                Rule::when($this->input('production_type') === ProductionType::OneOff->value, ['max:1']),
            ],
            'description' => ['sometimes', 'array'],
            'description.it' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            ...$this->specsRules(),
            ...$this->powertrainRules(),
        ];
    }
}
