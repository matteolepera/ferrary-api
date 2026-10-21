<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $birth_date
 */
class Driver extends Model
{
    use HasFactory, LogsCatalogActivity;

    protected $fillable = ['first_name', 'last_name', 'slug', 'nationality', 'birth_date'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<Version, $this>
     */
    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(Version::class);
    }
}
