<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Pubblico · Cataloghi', weight: 3)]
class CategoryController extends Controller
{
    /**
     * Elenco delle famiglie con almeno un modello visibile, ciascuna con il numero dei suoi modelli.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            // Una famiglia senza modelli visibili porterebbe a un archivio vuoto
            ->whereHas('carModels', fn ($query) => $query->visible())
            ->withCount(['carModels' => fn ($query) => $query->visible()])
            // L'ordine deciso dallo staff, poi l'id a parità di posizione
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }
}
