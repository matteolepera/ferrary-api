<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, HasTranslations, LogsCatalogActivity;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'sort_order'];

    /**
     * @return HasMany<CarModel, $this>
     */
    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }
}
