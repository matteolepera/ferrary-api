<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\VersionDetailsRules;
use App\Models\Version;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', new Slug, 'unique:versions,slug'],
            ...$this->versionRules(),
            'description' => ['sometimes', 'array'],
            'description.it' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            ...$this->specsRules(),
            ...$this->powertrainRules(),
        ];
    }
}
