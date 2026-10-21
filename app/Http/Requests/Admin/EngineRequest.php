<?php

namespace App\Http\Requests\Admin;

use App\Enums\Aspiration;
use App\Enums\EngineArchitecture;
use App\Models\Engine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida un motore, sia in creazione sia in modifica.
 */
class EngineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $engine = $this->route('engine');

        // Con un motore nell'indirizzo è una modifica, altrimenti una creazione
        return $engine instanceof Engine
            ? $this->user()->can('update', $engine)
            : $this->user()->can('create', Engine::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('engines', 'code')->ignore($this->route('engine'))],
            'name' => ['nullable', 'string', 'max:100'],
            'architecture' => ['required', Rule::enum(EngineArchitecture::class)],
            // L'angolo esiste solo per i motori a V
            'v_angle_deg' => ['nullable', 'integer', 'min:1', 'max:180', 'prohibited_unless:architecture,V'],
            'cylinders' => ['required', 'integer', 'min:1', 'max:24'],
            'displacement_cc' => ['required', 'integer', 'min:1', 'max:65535'],
            'bore_mm' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'stroke_mm' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'valves_per_cylinder' => ['nullable', 'integer', 'min:1', 'max:10'],
            'aspiration' => ['required', Rule::enum(Aspiration::class)],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.it' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
