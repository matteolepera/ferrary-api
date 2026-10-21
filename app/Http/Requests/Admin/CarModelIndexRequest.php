<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Models\CarModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarModelIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', CarModel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PublicationStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
