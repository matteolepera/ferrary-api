<?php

namespace App\Http\Requests\Admin;

use App\Models\Version;
use App\Models\VersionRacing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida un record sportivo, sia in creazione sia in modifica.
 */
class RacingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $version = $this->targetVersion();

        return $version !== null && $this->user()->can('update', $version);
    }

    /**
     * La versione del record: dall'indirizzo in creazione, dal record stesso in modifica.
     * Vale null solo quando la richiesta non arriva da una rotta, ad esempio mentre Scramble genera la documentazione.
     */
    public function targetVersion(): ?Version
    {
        $racing = $this->route('racing');

        /** @var Version|null */
        return $racing instanceof VersionRacing ? $racing->version : $this->route('version');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Un solo record per campionato nella stessa versione
            'championship' => [
                'required',
                'string',
                'max:100',
                Rule::unique('version_racing', 'championship')
                    ->where('version_id', $this->targetVersion()?->id)
                    ->ignore($this->route('racing')),
            ],
            'season_start' => ['required', 'integer', 'min:1947', 'max:2100'],
            'season_end' => ['nullable', 'integer', 'gte:season_start', 'max:2100'],
            'races' => ['nullable', 'integer', 'min:0', 'max:65535'],
            // Statistiche di squadra: i podi possono superare le gare, vittorie e pole no
            'wins' => [
                'nullable',
                'integer',
                'min:0',
                Rule::when(filled($this->input('races')), ['lte:races']),
                Rule::when(filled($this->input('podiums')), ['lte:podiums']),
            ],
            'poles' => [
                'nullable',
                'integer',
                'min:0',
                Rule::when(filled($this->input('races')), ['lte:races']),
            ],
            'podiums' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'fastest_laps' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'drivers_titles' => ['nullable', 'integer', 'min:0', 'max:255'],
            'constructors_titles' => ['nullable', 'integer', 'min:0', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'array'],
            'notes.it' => ['nullable', 'string', 'max:2000'],
            'notes.en' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
