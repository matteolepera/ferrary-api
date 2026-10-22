<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

#[Group('Autenticazione', weight: 0)]
class AuthController extends Controller
{
    /**
     * Effettua il login e restituisce l'utente collegato.
     */
    public function login(Request $request): UserResource
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email o password non corrette.',
            ]);
        }

        $request->session()->regenerate();

        return new UserResource($request->user());
    }

    /**
     * Chiude la sessione dell’utente collegato.
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * Restituisce l’utente collegato.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
