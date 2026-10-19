<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Regole comuni a tutti i model del catalogo.
 * Il master supera ogni controllo grazie a Gate::before in AppServiceProvider.
 */
abstract class CatalogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isMaster() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return true;
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
