<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aggiungere un'immagine equivale a modificare la versione
        return $this->user()->can('update', $this->route('version'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                // Solo immagini raster: niente SVG, che può contenere codice eseguibile
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('10mb')
                    ->dimensions(Rule::dimensions()->minWidth(800)),
            ],
            'alt_text' => ['required', 'array'],
            'alt_text.it' => ['required', 'string', 'max:255'],
            'alt_text.en' => ['nullable', 'string', 'max:255'],
            'is_cover' => ['sometimes', 'boolean'],
        ];
    }
}
