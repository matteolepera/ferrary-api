<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Come raccontare una voce del registro: l'azione in parole semplici, il tipo e il nome di ciò che è cambiato.
 * La usano il pannello e il registro delle attività, così le due pagine dicono le stesse cose.
 */
class ActivityPresenter
{
    /**
     * La voce in forma compatta, come la mostra il pannello.
     *
     * @return array{id: int, log: string|null, action: string, causer: mixed, subject_type: string, subject_name: string|null, created_at: string}
     */
    public function summary(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log' => $activity->log_name,
            'action' => $this->action($activity),
            'causer' => $activity->causer?->getAttribute('name'),
            'subject_type' => $this->subjectType($activity),
            'subject_name' => $this->subjectName($activity),
            'created_at' => $activity->created_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * L'azione in parole semplici: per Spatie pubblicare e correggere un nome sono entrambi "updated".
     */
    public function action(Activity $activity): string
    {
        $changed = data_get($activity->attribute_changes, 'attributes', []);

        if ($activity->event === 'updated' && is_array($changed)) {
            if (array_key_exists('published_at', $changed)) {
                $date = $changed['published_at'];

                return match (true) {
                    $date === null => 'unpublished',
                    // Confrontata con il momento della modifica, non con adesso
                    Carbon::parse($date)->isAfter($activity->created_at) => 'scheduled',
                    default => 'published',
                };
            }

            if (array_key_exists('role', $changed)) {
                return 'role_changed';
            }
        }

        return $activity->event ?? 'updated';
    }

    /**
     * Il tipo di ciò che è cambiato: "App\Models\CarModel" diventa "car_model".
     */
    public function subjectType(Activity $activity): string
    {
        return Str::snake(class_basename($activity->subject_type ?? ''));
    }

    /**
     * Il nome di ciò che è cambiato. Se il record non esiste più, lo recuperiamo dai valori salvati nel registro.
     */
    public function subjectName(Activity $activity): ?string
    {
        $name = $activity->subject?->getAttribute('name')
            ?? data_get($activity->attribute_changes, 'attributes.name')
            ?? data_get($activity->attribute_changes, 'old.name');

        return is_string($name) ? $name : null;
    }

    /**
     * Le modifiche, campo per campo, con il valore prima e dopo.
     * Una creazione ha solo i valori nuovi, un'eliminazione solo quelli vecchi.
     *
     * @return list<array{field: string, old: mixed, new: mixed}>
     */
    public function changes(Activity $activity): array
    {
        $new = data_get($activity->attribute_changes, 'attributes', []);
        $old = data_get($activity->attribute_changes, 'old', []);
        $new = is_array($new) ? $new : [];
        $old = is_array($old) ? $old : [];

        $fields = array_values(array_unique([...array_keys($new), ...array_keys($old)]));

        return array_map(fn (string|int $field) => [
            'field' => (string) $field,
            'old' => $old[$field] ?? null,
            'new' => $new[$field] ?? null,
        ], $fields);
    }
}
