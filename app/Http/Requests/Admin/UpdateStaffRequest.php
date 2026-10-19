<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Valida la modifica di nome, email e password di un membro dello staff.
 * Il ruolo non si modifica qui: ha un endpoint dedicato.
 */
class UpdateStaffRequest extends FormRequest
{
    /**
     * Qui il permesso NON dipende dai dati inviati, quindi possiamo controllarlo subito,
     * prima della validazione: chi non ha il permesso riceve 403 senza sapere nulla dei dati.
     */
    public function authorize(): bool
    {
        // route('user') è l'utente indicato nell'indirizzo, già caricato dal route model binding
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            // 'sometimes' → il campo viene validato solo se presente nella richiesta.
            // Così il frontend può inviare solo i campi modificati (es. solo il nome).
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            // ignore($user) → l'email deve essere unica, ma può restare quella attuale dell'utente stesso
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],

            'password' => ['sometimes', 'required', 'string', Password::min(8)],
        ];
    }
}
