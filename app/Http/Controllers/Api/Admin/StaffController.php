<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StaffController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Crea un nuovo membro dello staff.
     * Risponde 403 se l'utente non può assegnare il ruolo richiesto, 201 se la creazione riesce.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        // Qui i dati sono già validati: se non lo fossero, Laravel avrebbe risposto 422 prima di arrivare qui
        $role = UserRole::from($request->validated('role'));

        // Chiede alla UserPolicy: "questo utente può creare un membro con questo ruolo?"
        Gate::authorize('create', [User::class, $role]);

        // Nome, email e password sono nel $fillable del model, quindi possiamo assegnarli in blocco.
        // La password viene cifrata in automatico dal cast 'hashed'.
        $user = new User($request->safe()->only(['name', 'email', 'password']));

        // Il ruolo NON è nel $fillable: lo assegniamo esplicitamente, dopo aver verificato i permessi
        $user->role = $role;
        $user->save();

        // 201 Created: il codice HTTP standard per "risorsa creata"
        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
