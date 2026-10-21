<?php

use App\Enums\BodyType;
use App\Enums\BrakeType;
use App\Enums\ChassisMaterial;
use App\Enums\Drivetrain;
use App\Enums\GearboxType;
use App\Enums\ProductionType;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('converte i campi a scelta fissa in enum', function () {
    $version = Version::factory()->create(['body_type' => 'spider', 'production_type' => 'serie_limitata']);
    VersionSpec::factory()->create([
        'version_id' => $version->id,
        'chassis_material' => 'fibra_di_carbonio',
        'brakes' => 'carboceramici',
    ]);
    VersionPowertrain::factory()->create([
        'version_id' => $version->id,
        'gearbox_type' => 'manuale',
        'drivetrain' => 'posteriore',
    ]);

    $version = Version::with(['specs', 'powertrain'])->findOrFail($version->id);

    expect($version->body_type)->toBe(BodyType::Spider)
        ->and($version->production_type)->toBe(ProductionType::SerieLimitata)
        ->and($version->specs->chassis_material)->toBe(ChassisMaterial::FibraDiCarbonio)
        ->and($version->specs->brakes)->toBe(BrakeType::Carboceramici)
        ->and($version->powertrain->gearbox_type)->toBe(GearboxType::Manuale)
        ->and($version->powertrain->drivetrain)->toBe(Drivetrain::Posteriore);
});
