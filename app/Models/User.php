<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property UserRole $role
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'master_lock'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasActivity, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isMaster(): bool
    {
        return $this->role === UserRole::Master;
    }

    /**
     * Regole del registro per lo staff: solo nome, email e ruolo.
     * Password e token non vengono mai registrati, nemmeno in forma cifrata.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Qui usiamo una lista esplicita: è più sicuro elencare cosa registrare
            // che elencare cosa escludere, perché una colonna aggiunta in futuro resta fuori finché non la aggiungi tu
            ->logOnly(['name', 'email', 'role'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('staff');
    }
}
