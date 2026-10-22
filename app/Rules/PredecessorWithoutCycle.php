<?php

namespace App\Rules;

use App\Models\CarModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PredecessorWithoutCycle implements ValidationRule
{
    public function __construct(private ?CarModel $carModel) {}

    /**
     * Risale i predecessori di quello proposto: se incontra il modello stesso, c'è un ciclo.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Senza un modello da modificare non può esserci nessun ciclo
        if ($this->carModel === null) {
            return;
        }

        $current = CarModel::withTrashed()->find($value);

        while ($current !== null) {
            if ($current->is($this->carModel)) {
                $fail('Questo predecessore creerebbe un ciclo nella linea di discendenza.');

                return;
            }

            $current = $current->predecessor_id !== null
                ? CarModel::withTrashed()->find($current->predecessor_id)
                : null;
        }
    }
}
