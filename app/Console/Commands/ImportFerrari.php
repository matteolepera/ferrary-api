<?php

namespace App\Console\Commands;

use App\Models\CarModel;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Engine;
use App\Models\Version;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Riempie l'archivio dai file JSON di database/data: motori, designer, piloti, modelli e versioni.
 * Si può rilanciare: tutto è cercato per slug o per codice, e aggiornato invece che duplicato.
 */
class ImportFerrari extends Command
{
    protected $signature = 'ferrary:import {--path=database/data : La cartella con i file JSON}';

    protected $description = "Importa i dati dell'archivio dai file JSON";

    /** I campi della versione che arrivano così come sono dal file */
    private const VERSION_FIELDS = [
        'name',
        'vehicle_type',
        'body_type',
        'project_code',
        'seats',
        'year_start',
        'year_end',
        'in_production',
        'production_type',
        'units_produced',
        'description',
    ];

    public function handle(): int
    {
        $folder = (string) $this->option('path');
        $files = File::glob(str_starts_with($folder, '/') ? "{$folder}/*.json" : base_path("{$folder}/*.json"));

        if ($files === []) {
            $this->error("Nessun file JSON in {$folder}.");

            return self::FAILURE;
        }

        $data = collect($files)->map(fn (string $file) => $this->read($file));

        // Migliaia di "Sistema ha creato…" seppellirebbero nel registro il lavoro dello staff
        activity()->disableLogging();

        try {
            // Tutto o niente: con un dato sbagliato, il database resta com'era
            DB::transaction(function () use ($data) {
                $this->importCatalog($data->flatMap(fn (array $file) => $file['engines'] ?? []), Engine::class, 'code');
                $this->importCatalog($data->flatMap(fn (array $file) => $file['designers'] ?? []), Designer::class, 'slug');
                $this->importCatalog($data->flatMap(fn (array $file) => $file['drivers'] ?? []), Driver::class, 'slug');

                $models = $data->flatMap(fn (array $file) => $file['models'] ?? []);
                $models->each(fn (array $model) => $this->importModel($model));
                // Dopo tutti i modelli: un predecessore può stare in un altro file
                $models->each(fn (array $model) => $this->linkPredecessor($model));
            });
        } catch (RuntimeException $exception) {
            // Un errore nei dati: una riga che dice cosa manca e dove, invece di tutta la traccia
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            activity()->enableLogging();
        }

        $this->info(sprintf(
            'Importati %d modelli e %d versioni da %d file.',
            $data->sum(fn (array $file) => count($file['models'] ?? [])),
            $data->sum(fn (array $file) => collect($file['models'] ?? [])->sum(fn (array $model) => count($model['versions'] ?? []))),
            count($files),
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $file): array
    {
        $content = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($content)) {
            throw new RuntimeException('Il file '.basename($file).' non contiene un oggetto JSON.');
        }

        return $content;
    }

    /**
     * Motori, designer e piloti: cercati per la loro chiave, creati o aggiornati con il resto dei campi.
     *
     * @param  Collection<int, mixed>  $items
     * @param  class-string<Engine|Designer|Driver>  $class
     */
    private function importCatalog(Collection $items, string $class, string $key): void
    {
        foreach ($items as $item) {
            $class::updateOrCreate([$key => $item[$key]], Arr::except($item, [$key]));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importModel(array $data): void
    {
        $category = Category::where('slug', $data['category'])->first()
            ?? throw new RuntimeException("Categoria sconosciuta \"{$data['category']}\" nel modello {$data['slug']}.");

        $model = CarModel::updateOrCreate(
            ['slug' => $data['slug']],
            ['category_id' => $category->id, 'name' => $data['name'], 'description' => $data['description']],
        );
        $this->publish($model);

        foreach ($data['versions'] ?? [] as $position => $version) {
            $this->importVersion($model, $version, $position + 1);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importVersion(CarModel $model, array $data, int $position): void
    {
        $version = Version::updateOrCreate(
            ['car_model_id' => $model->id, 'slug' => $data['slug']],
            Arr::only($data, self::VERSION_FIELDS) + ['sort_order' => $data['sort_order'] ?? $position],
        );
        $this->publish($version);

        // Le sezioni: quelle del file sostituiscono quelle del database, e se mancano vengono tolte
        if (isset($data['powertrain'])) {
            $engine = $data['powertrain']['engine'] ?? null;
            $engineId = $engine === null ? null : (Engine::where('code', $engine)->value('id')
                ?? throw new RuntimeException("Motore sconosciuto \"{$engine}\" nella versione {$data['slug']}."));

            $version->powertrain()->updateOrCreate([], Arr::except($data['powertrain'], ['engine']) + ['engine_id' => $engineId]);
        } else {
            $version->powertrain()->delete();
        }

        if (isset($data['specs'])) {
            $version->specs()->updateOrCreate([], $data['specs']);
        } else {
            $version->specs()->delete();
        }

        // Lo stesso designer può comparire con due ruoli: si ricollegano uno per uno
        $version->designers()->detach();
        foreach ($data['designers'] ?? [] as $credit) {
            $designerId = Designer::where('slug', $credit['slug'])->value('id')
                ?? throw new RuntimeException("Designer sconosciuto \"{$credit['slug']}\" nella versione {$data['slug']}.");
            $version->designers()->attach($designerId, ['role' => $credit['role']]);
        }

        $version->drivers()->sync(collect($data['drivers'] ?? [])->map(
            fn (string $slug) => Driver::where('slug', $slug)->value('id')
                ?? throw new RuntimeException("Pilota sconosciuto \"{$slug}\" nella versione {$data['slug']}."),
        )->all());

        $version->racingRecords()->delete();
        foreach ($data['racing'] ?? [] as $record) {
            $version->racingRecords()->create($record);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function linkPredecessor(array $data): void
    {
        $predecessor = $data['predecessor'] ?? null;
        $predecessorId = $predecessor === null ? null : (CarModel::where('slug', $predecessor)->value('id')
            ?? throw new RuntimeException("Predecessore sconosciuto \"{$predecessor}\" nel modello {$data['slug']}."));

        CarModel::where('slug', $data['slug'])->update(['predecessor_id' => $predecessorId]);
    }

    /**
     * Pubblica un record appena importato; se era già pubblicato, la sua data non cambia.
     */
    private function publish(CarModel|Version $record): void
    {
        if ($record->published_at === null) {
            $record->forceFill(['published_at' => now()])->save();
        }
    }
}
