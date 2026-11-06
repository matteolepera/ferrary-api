<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /**
     * Il modello che rappresenta la famiglia: il visibile pubblicato più di recente.
     * La sua copertina è la foto della famiglia nella home.
     *
     * @return HasOne<CarModel, $this>
     */
    public function featuredModel(): HasOne
    {
        return $this->hasOne(CarModel::class)->ofMany(
            // Tra tutti i modelli prende quello con la data di pubblicazione più recente...
            ['published_at' => 'max'],
            // ...considerando solo quelli che il pubblico può vedere
            fn ($query) => $query->visible(),
        );
    }
}
