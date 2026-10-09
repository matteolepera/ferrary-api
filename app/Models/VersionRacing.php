<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class VersionRacing extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'version_racing';

    public array $translatable = ['notes'];

    protected $fillable = [
        'version_id',
        'championship',
        'season_start',
        'season_end',
        'races',
        'wins',
        'poles',
        'podiums',
        'fastest_laps',
        'drivers_titles',
        'constructors_titles',
        'notes',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
