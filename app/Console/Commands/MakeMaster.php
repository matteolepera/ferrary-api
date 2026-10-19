<?php

namespace App\Console\Commands;

use App\Actions\TransferMasterRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeMaster extends Command
{
    protected $signature = 'app:make-master {email : Email dell\'utente da nominare master}';

    protected $description = 'Crea un utente master o trasferisce il ruolo a un utente esistente';

    public function handle(TransferMasterRole $transferMasterRole): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$email}\" non è un indirizzo email valido.");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user?->isMaster()) {
            $this->info("{$user->name} è già il master.");

            return self::SUCCESS;
        }

        if ($user === null) {
            if (! confirm("Nessun utente con email {$email}. Vuoi crearlo?")) {
                return self::FAILURE;
            }

            $user = new User;
            $user->email = $email;
            $user->name = text('Nome', required: true);
            $user->password = password(
                'Password',
                required: true,
                validate: fn (string $value) => strlen($value) < 8
                    ? 'La password deve avere almeno 8 caratteri.'
                    : null,
            );
            $user->save();
        } else {
            $currentMaster = User::where('role', UserRole::Master)->first();

            $question = $currentMaster
                ? "Il ruolo di master passerà da {$currentMaster->name} a {$user->name}, e {$currentMaster->name} diventerà admin. Confermi?"
                : "{$user->name} diventerà master. Confermi?";

            if (! confirm($question)) {
                return self::FAILURE;
            }
        }

        $transferMasterRole->handle($user);

        $this->info("{$user->name} è ora il master.");

        return self::SUCCESS;
    }
}
