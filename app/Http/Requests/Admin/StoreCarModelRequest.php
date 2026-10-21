<?php

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCarModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CarModel::class);
    }

    /**
     * Se lo slug manca, lo genera dal nome.
     */
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
            // unique conta anche i modelli nel cestino, come il vincolo del database
            'slug' => ['required', 'string', 'max:255', new Slug, 'unique:car_models,slug'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'predecessor_id' => ['nullable', 'integer', 'exists:car_models,id'],
            'description' => ['required', 'array'],
            'description.it' => ['required', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
