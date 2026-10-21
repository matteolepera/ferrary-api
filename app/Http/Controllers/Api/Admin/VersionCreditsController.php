<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\DesignerRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DesignerResource;
use App\Http\Resources\Admin\DriverResource;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Collegamenti tra una versione e i suoi designer e piloti.
 */
class VersionCreditsController extends Controller
{
    /**
     * Sostituisce i designer della versione con l'elenco ricevuto.
     */
    public function designers(Request $request, Version $version): AnonymousResourceCollection
    {
        Gate::authorize('update', $version);

        $data = $request->validate([
            // 'present': il campo deve esserci, ma può essere un elenco vuoto
            'designers' => ['present', 'array'],
            'designers.*.id' => ['required', 'integer', 'exists:designers,id'],
            'designers.*.role' => ['required', Rule::enum(DesignerRole::class)],
        ]);

        // La stessa coppia designer + ruolo conta una volta sola
        $credits = collect($data['designers'])
            ->unique(fn (array $credit) => $credit['id'].'-'.$credit['role']);

        DB::transaction(function () use ($version, $credits) {
            $version->designers()->detach();

            foreach ($credits as $credit) {
                $version->designers()->attach($credit['id'], ['role' => $credit['role']]);
            }
        });

        return DesignerResource::collection($version->designers()->orderBy('name')->get());
    }

    /**
     * Sostituisce i piloti della versione con l'elenco ricevuto.
     */
    public function drivers(Request $request, Version $version): AnonymousResourceCollection
    {
        Gate::authorize('update', $version);

        $data = $request->validate([
            'drivers' => ['present', 'array'],
            'drivers.*' => ['integer', 'distinct', 'exists:drivers,id'],
        ]);

        // sync: aggiunge i nuovi, toglie quelli non più presenti, lascia gli altri
        $version->drivers()->sync($data['drivers']);

        return DriverResource::collection($version->drivers()->orderBy('last_name')->get());
    }
}
