<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\VersionDetailsRules;
use App\Models\Version;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateVersionRequest extends FormRequest
{
    use VersionDetailsRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('version'));
    }

    /**
     * Completa la richiesta con i valori attuali dei campi legati da vincoli:
     * così la validazione controlla lo stato finale della versione.
     */
    protected function prepareForValidation(): void
    {
        /** @var Version $version */
        $version = $this->route('version');

        $current = Arr::only($version->attributesToArray(), [
            'vehicle_type',
            'body_type',
            'year_start',
            'year_end',
            'in_production',
            'production_type',
            'units_produced',
        ]);

        // Aggiunge solo i campi che la richiesta non contiene
        $this->merge(array_diff_key($current, $this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Version $version */
        $version = $this->route('version');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                new Slug,
                Rule::unique('versions', 'slug')->ignore($version),
            ],
            ...$this->versionRules(),
            'description' => ['sometimes', 'nullable', 'array'],
            'description.it' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            ...$this->specsRules(),
            ...$this->powertrainRules(),
        ];
    }
}
