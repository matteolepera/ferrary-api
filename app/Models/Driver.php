<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'slug', 'nationality', 'birth_date'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(Version::class);
    }
}
