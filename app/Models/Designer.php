<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designer extends Model
{
    use HasFactory, LogsCatalogActivity;

    protected $fillable = ['name', 'slug', 'type', 'country'];

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(Version::class)->withPivot('role');
    }
}
