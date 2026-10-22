<?php

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use App\Rules\PredecessorWithoutCycle;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('carModel'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CarModel|null $carModel */
        $carModel = $this->route('carModel');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                new Slug,
                Rule::unique('car_models', 'slug')->ignore($carModel),
            ],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'predecessor_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:car_models,id',
                new PredecessorWithoutCycle($carModel),
            ],
            'description' => ['sometimes', 'array'],
            'description.it' => ['required_with:description', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
