<?php

use App\Enums\PowertrainType;
use App\Enums\UserRole;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Il .env.testing non contiene le variabili del master: le impostiamo qui,
// così il test funziona su qualsiasi computer, indipendentemente dalla configurazione
beforeEach(function () {
    config([
        'ferrary.master.email' => 'master@ferrary.test',
        'ferrary.master.password' => 'password-di-test',
    ]);
});

it('popola il database con i dati iniziali', function () {
    // $this->seed() esegue il DatabaseSeeder, come "php artisan db:seed"
    $this->seed();

    expect(User::where('role', UserRole::Master)->count())->toBe(1)
        ->and(CarModel::whereNotNull('published_at')->count())->toBe(4)
        ->and(Version::where('slug', 'sf90-stradale')->firstOrFail()->powertrain->powertrain_type)->toBe(PowertrainType::Ibrido)
        ->and(Version::where('slug', 'f2004')->firstOrFail()->drivers)->toHaveCount(2);
});

it('assegna le Ferrari di esempio alle famiglie di Ferrari', function () {
    $this->seed();

    $family = fn (string $slug) => CarModel::where('slug', $slug)->firstOrFail()->category->slug;

    expect($family('812'))->toBe('sportive')
        ->and($family('sf90'))->toBe('sportive')
        ->and($family('f40'))->toBe('hypercar')
        ->and($family('f2004'))->toBe('formula-1')
        ->and(Category::orderBy('sort_order')->pluck('slug')->first())->toBe('sportive');
});

it('si può eseguire più volte senza creare duplicati', function () {
    // Grazie a updateOrCreate, eseguire i seeder due volte non deve duplicare nulla
    $this->seed();
    $this->seed();

    expect(CarModel::count())->toBe(4)
        ->and(User::count())->toBe(1);
});
