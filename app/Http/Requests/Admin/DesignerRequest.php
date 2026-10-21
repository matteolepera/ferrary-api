<?php

namespace App\Http\Requests\Admin;

use App\Enums\DesignerType;
use App\Models\Designer;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Valida un designer, sia in creazione sia in modifica.
 */
class DesignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $designer = $this->route('designer');

        return $designer instanceof Designer
            ? $this->user()->can('update', $designer)
            : $this->user()->can('create', Designer::class);
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
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:170',
                new Slug,
                Rule::unique('designers', 'slug')->ignore($this->route('designer')),
            ],
            'type' => ['required', Rule::enum(DesignerType::class)],
            // Codice ISO del paese: due lettere maiuscole (IT, GB, DE...)
            'country' => ['nullable', 'string', 'size:2', 'uppercase'],
        ];
    }
}
