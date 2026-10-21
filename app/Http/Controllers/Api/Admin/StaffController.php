<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\TransferMasterRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

#[Group('Admin · Staff', weight: 10)]
class StaffController extends Controller
{
    /**
     * Elenco dello staff, ordinato per ruolo e nome.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    /**
     * Dettaglio di un membro dello staff.
     */
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

    /**
     * Modifica nome, email o password di un membro dello staff.
     * Il permesso è già stato verificato da UpdateStaffRequest.
     */
    public function update(UpdateStaffRequest $request, User $user): UserResource
    {
        // validated() contiene solo i campi inviati e validati: aggiorniamo solo quelli
        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * Elimina definitivamente un membro dello staff.
     * Risponde 204 se riesce, 403 se l'utente non può eliminarlo (compreso il master su sé stesso).
     */
    public function destroy(User $user): Response
    {
        Gate::authorize('delete', $user);

        $user->delete();

        // 204 No Content: operazione riuscita, nessun contenuto da restituire
        return response()->noContent();
    }

    /**
     * Cambia il ruolo di un membro dello staff (admin ↔ moderatore).
     * Il ruolo di master non si assegna da qui: serve il trasferimento.
     */
    public function changeRole(Request $request, User $user): UserResource
    {
        // 1. Chi fa la richiesta può gestire questo utente?
        //    Lo controlliamo subito: chi non ha alcun potere su di lui riceve 403 senza passare dalla validazione.
        Gate::authorize('update', $user);

        // 2. Il ruolo inviato esiste? Per un solo campo basta validare qui, senza una Form Request dedicata.
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $role = UserRole::from($validated['role']);

        // 3. Può assegnare proprio questo ruolo? (es. un admin non può promuovere ad admin)
        Gate::authorize('changeRole', [$user, $role]);

        $user->role = $role;
        $user->save();

        return new UserResource($user);
    }

    /**
     * Trasferisce il ruolo di master a un altro membro dello staff.
     * Solo il master può farlo, e dopo il trasferimento diventa admin.
     */
    public function transferMaster(User $user, TransferMasterRole $transferMasterRole): UserResource
    {
        Gate::authorize('transferMaster', $user);

        // La logica (transazione, ordine declassa-poi-promuovi) è nell'azione, la stessa usata dal comando Artisan
        $transferMasterRole->handle($user);

        // fresh() ricarica l'utente dal database, così la risposta mostra il ruolo aggiornato
        return new UserResource($user->fresh());
    }
}
