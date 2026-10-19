<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Valida i dati per creare un nuovo membro dello staff.
 * I permessi (chi può creare quale ruolo) li controlla il controller tramite la UserPolicy.
 */
class StoreStaffRequest extends FormRequest
{
    /**
     * Restituiamo sempre true: il controllo dei permessi dipende dal ruolo richiesto,
     * che è affidabile solo dopo la validazione. Per questo lo fa il controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // unique:users,email → l'email non deve essere già usata da un altro utente
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            // Password::min(8) → almeno 8 caratteri; si possono aggiungere altre regole (numeri, simboli...)
            'password' => ['required', 'string', Password::min(8)],

            // Rule::enum → il valore deve essere uno di quelli dell'enum: master, admin o moderator
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }
}
