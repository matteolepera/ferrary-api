<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ActivityResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

/**
 * Il registro delle attività: tutte le modifiche dello staff, con i filtri.
 */
class ActivityController extends Controller
{
    /** I tipi di elemento che si possono filtrare: i modelli registrati nel log del catalogo e dello staff */
    private const SUBJECTS = [
        'car_model',
        'version',
        'version_spec',
        'version_powertrain',
        'image',
        'engine',
        'designer',
        'driver',
        'category',
        'user',
    ];

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        // Lo stesso permesso dello staff: Master e Admin
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'log' => ['nullable', Rule::in(['catalogo', 'staff'])],
            'causer_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', Rule::in(self::SUBJECTS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $activities = Activity::query()
            // Il log di sistema resta fuori: riguarda operazioni interne
            ->whereIn('log_name', ['catalogo', 'staff'])
            ->when($filters['log'] ?? null, fn ($query, $log) => $query->where('log_name', $log))
            ->when($filters['causer_id'] ?? null, fn ($query, $id) => $query->where('causer_id', $id))
            // "car_model" diventa "App\Models\CarModel", il valore salvato nella colonna
            ->when($filters['subject_type'] ?? null, fn ($query, $type) => $query->where('subject_type', 'App\\Models\\'.Str::studly($type)))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->with(['causer', 'subject'])
            ->latest('id')
            ->paginate(25)
            // I filtri restano nei link delle pagine successive
            ->withQueryString();

        return ActivityResource::collection($activities);
    }
}
