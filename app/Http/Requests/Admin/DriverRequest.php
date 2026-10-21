<?php

namespace App\Http\Requests\Admin;

use App\Models\Driver;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Valida un pilota, sia in creazione sia in modifica.
 */
class DriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $driver = $this->route('driver');

        return $driver instanceof Driver
            ? $this->user()->can('update', $driver)
            : $this->user()->can('create', Driver::class);
    }

    /**
     * Se lo slug manca, lo genera da nome e cognome.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('first_name')) && filled($this->input('last_name'))) {
            $this->merge(['slug' => Str::slug($this->input('first_name').' '.$this->input('last_name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:200',
                new Slug,
                Rule::unique('drivers', 'slug')->ignore($this->route('driver')),
            ],
            'nationality' => ['nullable', 'string', 'size:2', 'uppercase'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ];
    }
}
