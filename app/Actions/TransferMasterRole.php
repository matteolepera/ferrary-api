<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferMasterRole
{
    public function handle(User $newMaster): void
    {
        DB::transaction(function () use ($newMaster) {
            User::where('role', UserRole::Master)
                ->whereKeyNot($newMaster->id)
                ->update(['role' => UserRole::Admin]);

            $newMaster->forceFill(['role' => UserRole::Master])->save();
        });
    }
}
