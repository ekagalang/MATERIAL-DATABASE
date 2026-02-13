<?php

namespace App\Console\Commands;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\StoreLocation;
use Illuminate\Console\Command;

class BackfillStoreMaterialAvailabilityCommand extends Command
{
    protected $signature = 'stores:backfill-availability {--dry-run : Show what would be linked without saving}';

    protected $description = 'Backfill store_material_availabilities from existing material.store_location_id (non-destructive).';

    /**
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private array $materialModels = [
        'bricks' => Brick::class,
        'cements' => Cement::class,
        'sands' => Sand::class,
        'cats' => Cat::class,
        'ceramics' => Ceramic::class,
        'nats' => Nat::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN mode aktif. Tidak ada data yang diubah.');
        }

        $totalChecked = 0;
        $totalLinked = 0;
        $totalMissingLocation = 0;

        foreach ($this->materialModels as $label => $modelClass) {
            $checked = 0;
            $linked = 0;
            $missingLocation = 0;

            $modelClass::query()
                ->whereNotNull('store_location_id')
                ->chunkById(500, function ($rows) use (
                    &$checked,
                    &$linked,
                    &$missingLocation,
                    $dryRun,
                ) {
                    foreach ($rows as $material) {
                        $checked++;
                        $locationId = (int) $material->store_location_id;
                        if ($locationId <= 0) {
                            continue;
                        }

                        $locationExists = StoreLocation::query()->whereKey($locationId)->exists();
                        if (!$locationExists) {
                            $missingLocation++;
                            continue;
                        }

                        if (!method_exists($material, 'storeLocations')) {
                            continue;
                        }

                        $alreadyLinked = $material
                            ->storeLocations()
                            ->where('store_location_id', $locationId)
                            ->exists();

                        if ($alreadyLinked) {
                            continue;
                        }

                        if (!$dryRun) {
                            $material->storeLocations()->syncWithoutDetaching([$locationId]);
                        }
                        $linked++;
                    }
                });

            $this->line(
                sprintf(
                    '%s => checked: %d, linked: %d, missing_location: %d',
                    $label,
                    $checked,
                    $linked,
                    $missingLocation,
                ),
            );

            $totalChecked += $checked;
            $totalLinked += $linked;
            $totalMissingLocation += $missingLocation;
        }

        $this->newLine();
        $this->info(
            sprintf(
                'Summary: checked=%d, linked=%d, missing_location=%d',
                $totalChecked,
                $totalLinked,
                $totalMissingLocation,
            ),
        );

        if ($dryRun) {
            $this->warn('Jalankan ulang tanpa --dry-run untuk menerapkan perubahan.');
        }

        return self::SUCCESS;
    }
}

