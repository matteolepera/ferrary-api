<?php

namespace App\Models\Concerns;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gestisce la pubblicazione dei model con la colonna published_at.
 * - published_at NULL      → bozza, mai visibile al pubblico
 * - published_at nel passato → pubblicato
 * - published_at nel futuro  → pubblicazione programmata: diventa visibile da sola a quella data
 */
trait Publishable
{
    /**
     * Scope: filtra solo i record visibili al pubblico.
     * Si usa scrivendo ->published() in una query, es. CarModel::published()->get().
     */
    public function scopePublished(Builder $query): void
    {
        // qualifyColumn aggiunge il nome della tabella ("car_models.published_at"):
        // evita ambiguità se un giorno la query unirà più tabelle con la stessa colonna
        $query->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    /**
     * Lo stesso controllo, su un singolo record già caricato.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * Stato di pubblicazione, per le etichette dell'admin.
     */
    public function publicationStatus(): PublicationStatus
    {
        if ($this->published_at === null) {
            return PublicationStatus::Draft;
        }

        return $this->published_at->isFuture()
            ? PublicationStatus::Scheduled
            : PublicationStatus::Published;
    }

    /**
     * Scope: filtra per stato di pubblicazione.
     */
    public function scopeStatus(Builder $query, PublicationStatus $status): void
    {
        $column = $this->qualifyColumn('published_at');

        match ($status) {
            PublicationStatus::Draft => $query->whereNull($column),
            PublicationStatus::Scheduled => $query->where($column, '>', now()),
            PublicationStatus::Published => $query->where($column, '<=', now()),
        };
    }
}
