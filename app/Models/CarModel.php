<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class CarModel extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['description'];

    protected $fillable = ['category_id', 'predecessor_id', 'name', 'slug', 'description'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'predecessor_id');
    }

    public function successors(): HasMany
    {
        return $this->hasMany(CarModel::class, 'predecessor_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Version::class)->orderBy('sort_order');
    }
}
