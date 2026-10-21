<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Categorie', weight: 11)]
class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('carModels')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('view', $category);

        // Come withCount, ma su un record già caricato
        $category->loadCount('carModels');

        return new CategoryResource($category);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Salva solo le traduzioni compilate: un inglese vuoto non va salvato come stringa vuota
        $data['name'] = array_filter($data['name'], 'filled');

        $category = Category::create($data);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            // Sostituisce tutte le traduzioni: una lingua svuotata viene rimossa
            $category->replaceTranslations('name', array_filter($data['name'], 'filled'));
            unset($data['name']);
        }

        $category->fill($data)->save();

        return new CategoryResource($category);
    }

    public function destroy(Category $category): Response
    {
        Gate::authorize('delete', $category);

        // Contano anche i modelli nel cestino: potrebbero essere ripristinati
        abort_if(
            $category->carModels()->withTrashed()->exists(),
            409,
            'La categoria contiene dei modelli: spostali in un\'altra categoria prima di eliminarla.',
        );

        $category->delete();

        return response()->noContent();
    }
}
