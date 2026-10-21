<?php

namespace App\Http\Requests\Admin;

use App\Models\Image;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Image $image */
        $image = $this->route('image');

        return $this->user()->can('update', $image->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alt_text' => ['sometimes', 'array'],
            'alt_text.it' => ['required_with:alt_text', 'string', 'max:255'],
            'alt_text.en' => ['nullable', 'string', 'max:255'],
            // Si può solo impostare come copertina, non toglierla
            'is_cover' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_cover.accepted' => 'Per cambiare copertina, imposta come copertina un\'altra immagine.',
        ];
    }
}
