<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('ferrary.master.email');
        $password = config('ferrary.master.password');

        if (! $email || ! $password) {
            $this->command->warn('MASTER_EMAIL o MASTER_PASSWORD non impostati: utente master non creato.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => config('ferrary.master.name'),
            'password' => $password,
            'role' => UserRole::Master,
        ])->save();
    }
}
