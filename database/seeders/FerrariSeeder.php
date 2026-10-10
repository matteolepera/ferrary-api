<?php

namespace Database\Seeders;

use App\Enums\VehicleType;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Engine;
use App\Models\Version;
use Illuminate\Database\Seeder;

/**
 * Prime Ferrari dell'archivio.
 * I valori incerti sono lasciati a null e segnati con TODO: vanno verificati su fonti ufficiali.
 */
class FerrariSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedF40();
        $this->seed812();
        $this->seedSf90();
        $this->seedF2004();
    }

    private function seedF40(): void
    {
        $model = $this->carModel('hypercar', [
            'name' => 'F40',
            'slug' => 'f40',
            'description' => [
                'it' => "Presentata nel 1987 per i quarant'anni della Casa, è l'ultima Ferrari presentata da Enzo Ferrari.",
                'en' => "Unveiled in 1987 for the company's 40th anniversary, the last Ferrari presented by Enzo Ferrari.",
            ],
        ]);

        $version = $this->version($model, [
            'name' => 'F40',
            'slug' => 'f40',
            'vehicle_type' => VehicleType::Stradale,
            'body_type' => 'coupe',
            'seats' => 2,
            'year_start' => 1987,
            'year_end' => 1992,
            'production_type' => 'serie_limitata',
            'units_produced' => 1311,
        ]);

        $version->specs()->updateOrCreate([], [
            'length_mm' => 4358,
            'width_mm' => 1970,
            'height_mm' => 1124,
            'wheelbase_mm' => 2450,
            'dry_weight_kg' => 1100,
            'chassis_material' => 'misto',
            'brakes' => 'disco_acciaio',
            'top_speed_kmh' => 324,
            'zero_to_100_sec' => 4.1,
        ]);

        $version->powertrain()->updateOrCreate([], [
            'engine_id' => $this->engineId('F120A'),
            'ice_power_cv' => 478,
            'ice_torque_nm' => 577,
            'ice_max_rpm' => 7000,
            'gearbox_type' => 'manuale',
            'gears' => 5,
            'drivetrain' => 'posteriore',
        ]);

        $this->attachDesigners($version, ['pininfarina', 'leonardo-fioravanti']);
    }

    private function seed812(): void
    {
        $model = $this->carModel('berlinetta-v12', [
            'name' => '812',
            'slug' => '812',
            'description' => [
                'it' => 'Famiglia di berlinette V12 a motore anteriore centrale, erede della F12berlinetta.',
                'en' => 'Family of front-mid-engined V12 berlinettas, successor to the F12berlinetta.',
            ],
        ]);

        $version = $this->version($model, [
            'name' => '812 Superfast',
            'slug' => '812-superfast',
            'vehicle_type' => VehicleType::Stradale,
            'body_type' => 'coupe',
            'seats' => 2,
            'project_code' => 'F152M',
            'year_start' => 2017,
            'year_end' => null, // TODO: verificare l'anno di fine produzione
            'production_type' => 'serie',
        ]);

        $version->specs()->updateOrCreate([], [
            'length_mm' => 4657,
            'width_mm' => 1971,
            'height_mm' => 1276,
            'wheelbase_mm' => 2720,
            'dry_weight_kg' => 1525,
            'weight_front_pct' => 47,
            'chassis_material' => 'alluminio',
            'brakes' => 'carboceramici',
            'top_speed_kmh' => 340,
            'zero_to_100_sec' => 2.9,
            'zero_to_200_sec' => 7.9,
        ]);

        $version->powertrain()->updateOrCreate([], [
            'engine_id' => $this->engineId('F140GA'),
            'ice_power_cv' => 800,
            'ice_torque_nm' => 718,
            'ice_max_rpm' => 8500,
            'gearbox_type' => 'doppia_frizione',
            'gears' => 7,
            'drivetrain' => 'posteriore',
        ]);

        $this->attachDesigners($version, ['centro-stile-ferrari', 'flavio-manzoni']);
    }

    private function seedSf90(): void
    {
        $model = $this->carModel('berlinetta-v8', [
            'name' => 'SF90',
            'slug' => 'sf90',
            'description' => [
                'it' => 'Prima Ferrari ibrida plug-in di serie, presentata nel 2019.',
                'en' => 'The first series-production plug-in hybrid Ferrari, unveiled in 2019.',
            ],
        ]);

        $version = $this->version($model, [
            'name' => 'SF90 Stradale',
            'slug' => 'sf90-stradale',
            'vehicle_type' => VehicleType::Stradale,
            'body_type' => 'coupe',
            'seats' => 2,
            'project_code' => 'F173',
            'year_start' => 2019,
            'year_end' => null, // TODO: verificare lo stato di produzione
            'production_type' => 'serie',
        ]);

        $version->specs()->updateOrCreate([], [
            'length_mm' => 4710,
            'width_mm' => 1972,
            'height_mm' => 1186,
            'wheelbase_mm' => 2650,
            'dry_weight_kg' => 1570,
            'chassis_material' => 'misto',
            'brakes' => 'carboceramici',
            'top_speed_kmh' => 340,
            'zero_to_100_sec' => 2.5,
            'zero_to_200_sec' => 6.7,
            'fiorano_lap_sec' => 79.00,
        ]);

        $version->powertrain()->updateOrCreate([], [
            'engine_id' => $this->engineId('F154FA'),
            'ice_power_cv' => 780,
            'ice_torque_nm' => 800,
            'ice_max_rpm' => 7500,
            'electric_motors_count' => 3,
            'electric_power_cv' => 220,
            'battery_kwh' => 7.9,
            'electric_range_km' => 25,
            'system_power_cv' => 1000,
            'gearbox_type' => 'doppia_frizione',
            'gears' => 8,
            'drivetrain' => 'integrale',
        ]);

        $this->attachDesigners($version, ['centro-stile-ferrari', 'flavio-manzoni']);
    }

    private function seedF2004(): void
    {
        $model = $this->carModel('formula-1', [
            'name' => 'F2004',
            'slug' => 'f2004',
            'description' => [
                'it' => 'Monoposto della stagione 2004 di Formula 1, con cui la Scuderia vinse il titolo piloti e il titolo costruttori.',
                'en' => 'The 2004 Formula 1 car that won both the drivers\' and constructors\' championships for the Scuderia.',
            ],
        ]);

        $version = $this->version($model, [
            'name' => 'F2004',
            'slug' => 'f2004',
            'vehicle_type' => VehicleType::F1,
            'body_type' => 'monoposto',
            'seats' => 1,
            'year_start' => 2004,
            'year_end' => 2004,
            'production_type' => null,
        ]);

        $version->powertrain()->updateOrCreate([], [
            'engine_id' => $this->engineId('Tipo 053'),
            'gearbox_type' => 'sequenziale',
            'gears' => 7,
            'drivetrain' => 'posteriore',
        ]);

        $version->racingRecords()->updateOrCreate(['championship' => 'Formula 1'], [
            'season_start' => 2004,
            'season_end' => 2004,
            'races' => 18,
            'wins' => 15,
            'poles' => 12,
            'podiums' => 29,
            'drivers_titles' => 1,
            'constructors_titles' => 1,
        ]);

        $version->drivers()->syncWithoutDetaching(
            Driver::whereIn('slug', ['michael-schumacher', 'rubens-barrichello'])->pluck('id')->all(),
        );
    }

    private function carModel(string $categorySlug, array $attributes): CarModel
    {
        $model = CarModel::updateOrCreate(
            ['slug' => $attributes['slug']],
            [...$attributes, 'category_id' => Category::where('slug', $categorySlug)->firstOrFail()->id],
        );

        $this->publish($model);

        return $model;
    }

    private function version(CarModel $model, array $attributes): Version
    {
        $version = $model->versions()->updateOrCreate(['slug' => $attributes['slug']], $attributes);

        $this->publish($version);

        return $version;
    }

    private function publish(CarModel|Version $record): void
    {
        if ($record->published_at === null) {
            $record->published_at = now();
            $record->save();
        }
    }

    private function engineId(string $code): int
    {
        return Engine::where('code', $code)->firstOrFail()->id;
    }

    private function attachDesigners(Version $version, array $slugs): void
    {
        $designers = Designer::whereIn('slug', $slugs)->pluck('id')
            ->mapWithKeys(fn (int $id) => [$id => ['role' => 'design']])
            ->all();

        $version->designers()->syncWithoutDetaching($designers);
    }
}
