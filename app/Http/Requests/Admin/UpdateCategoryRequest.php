<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'array'],
            // Se si modifica il nome, l'italiano resta obbligatorio
            'name.it' => ['required_with:name', 'string', 'max:100'],
            'name.en' => ['nullable', 'string', 'max:100'],
            // Nessuna rigenerazione automatica: cambiare lo slug cambia gli indirizzi del sito
            'slug' => [
                'sometimes',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($category),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
