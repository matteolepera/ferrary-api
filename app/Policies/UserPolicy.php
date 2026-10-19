<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Regole per la gestione dello staff.
 * Il ruolo di master non si assegna mai con queste operazioni: cambia solo con il trasferimento.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== UserRole::Moderator;
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $user->role !== UserRole::Moderator;
    }

    public function create(User $user, UserRole $role): bool
    {
        return $this->canAssign($user, $role);
    }

    public function update(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function changeRole(User $user, User $target, UserRole $role): bool
    {
        return $this->manages($user, $target) && $this->canAssign($user, $role);
    }

    public function transferMaster(User $user, User $target): bool
    {
        return $user->isMaster() && ! $user->is($target);
    }

    private function manages(User $user, User $target): bool
    {
        return match ($user->role) {
            UserRole::Master => ! $target->isMaster(),
            UserRole::Admin => $target->role === UserRole::Moderator,
            UserRole::Moderator => false,
        };
    }

    private function canAssign(User $user, UserRole $role): bool
    {
        return match ($user->role) {
            UserRole::Master => $role !== UserRole::Master,
            UserRole::Admin => $role === UserRole::Moderator,
            UserRole::Moderator => false,
        };
    }
}
