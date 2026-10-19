<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Registra nel log "catalogo" ogni modifica ai model che usano questo trait.
 * Il pacchetto rileva da solo chi ha fatto la modifica: l'utente collegato in quel momento.
 */
trait LogsCatalogActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Registra tutte le colonne del model...
            ->logAll()
            // ...tranne i timestamp, che cambiano a ogni salvataggio e renderebbero il log rumoroso
            ->logExcept(['created_at', 'updated_at'])
            // In una modifica, salva solo i campi effettivamente cambiati (con il valore prima e dopo)
            ->logOnlyDirty()
            // Se nessun campo è cambiato davvero, non crea nessuna riga
            ->dontLogEmptyChanges()
            // Nome del log: permette di separare le attività del catalogo da quelle dello staff
            ->useLogName('catalogo');
    }
}
