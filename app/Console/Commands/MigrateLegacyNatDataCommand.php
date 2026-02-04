<?php

namespace App\Console\Commands;

use App\Models\Cement;
use App\Models\Nat;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyNatDataCommand extends Command
{
    protected $signature = 'nat:migrate-legacy
        {--dry-run : Preview migration without writing changes}
        {--limit=0 : Process only first N legacy rows (0 = all)}
        {--chunk=200 : Chunk size for processing}
        {--skip-match-existing : Do not try to relink existing unmapped nats}
        {--force : Skip confirmation prompt}';

    protected $description = 'Backfill nats table from legacy cements rows where type = Nat';

    public function handle(): int
    {
        if (!Schema::hasTable('nats') || !Schema::hasColumn('nats', 'legacy_cement_id')) {
            $this->warn('Legacy mapping column `nats.legacy_cement_id` is not available.');
            $this->line('Nat has been finalized as standalone material. This command is no longer applicable.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(1, (int) $this->option('chunk'));
        $matchExisting = !$this->option('skip-match-existing');

        $query = Cement::query()
            ->where('type', 'Nat')
            ->orderBy('id');

        $totalLegacyRows = (clone $query)->count();
        $total = $limit > 0 ? min($limit, $totalLegacyRows) : $totalLegacyRows;

        if ($limit > 0) {
            $query->limit($limit);
        }

        if ($total === 0) {
            $this->warn('No legacy Nat rows found in cements table.');
            return self::SUCCESS;
        }

        $this->info('Legacy Nat rows found: ' . $total);
        $this->line('Mode: ' . ($dryRun ? 'DRY-RUN (no writes)' : 'WRITE'));
        $this->line('Chunk size: ' . $chunk);
        $this->line('Match existing unmapped rows: ' . ($matchExisting ? 'yes' : 'no'));

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Proceed with migrating legacy Nat data into nats table?', true)) {
                $this->warn('Migration cancelled.');
                return self::SUCCESS;
            }
        }

        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'relinked' => 0,
        ];

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $processor = function (Collection $cements) use (&$stats, $progress, $dryRun, $matchExisting): void {
            $legacyIds = $cements->pluck('id')->all();
            $existing = Nat::query()
                ->whereIn('legacy_cement_id', $legacyIds)
                ->pluck('id', 'legacy_cement_id');

            foreach ($cements as $cement) {
                $payload = $this->mapCementToNatPayload($cement);
                $hasExisting = $existing->has($cement->id);
                $matchedNat = null;

                if (!$hasExisting && $matchExisting) {
                    $matchedNat = Nat::query()
                        ->whereNull('legacy_cement_id')
                        ->where('brand', $cement->brand)
                        ->where('sub_brand', $cement->sub_brand)
                        ->where('code', $cement->code)
                        ->where('color', $cement->color)
                        ->where('package_unit', $cement->package_unit)
                        ->where('package_weight_net', $cement->package_weight_net)
                        ->where('package_price', $cement->package_price)
                        ->where('store', $cement->store)
                        ->where('address', $cement->address)
                        ->orderBy('id')
                        ->first();
                }

                $action = 'created';
                if ($hasExisting) {
                    $action = 'updated';
                } elseif ($matchedNat) {
                    $action = 'relinked';
                }

                if ($dryRun) {
                    $stats[$action]++;
                    $stats['processed']++;
                    $progress->advance();
                    continue;
                }

                if ($hasExisting) {
                    Nat::query()->whereKey($existing[$cement->id])->update($payload);
                } elseif ($matchedNat) {
                    $matchedNat->update(array_merge($payload, [
                        'legacy_cement_id' => $cement->id,
                    ]));
                } else {
                    Nat::query()->create(array_merge($payload, [
                        'legacy_cement_id' => $cement->id,
                    ]));
                }

                $stats[$action]++;
                $stats['processed']++;
                $progress->advance();
            }
        };

        if ($limit > 0) {
            $allRows = $query->get();
            $allRows->chunk($chunk)->each($processor);
        } else {
            $query->chunkById($chunk, function ($cements) use ($processor) {
                $processor($cements);
            });
        }

        $progress->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Relinked Existing', $stats['relinked']],
            ],
        );

        $legacyTotal = Cement::query()->where('type', 'Nat')->count();
        $mappedTotal = Nat::query()->whereNotNull('legacy_cement_id')->count();
        $unmappedTotal = Nat::query()->whereNull('legacy_cement_id')->count();
        $missingMappings = max(0, $legacyTotal - $mappedTotal);

        $this->table(
            ['Post-check', 'Count'],
            [
                ['Legacy Nat rows (cements)', $legacyTotal],
                ['Mapped Nat rows (nats.legacy_cement_id not null)', $mappedTotal],
                ['Unmapped Nat rows (nats.legacy_cement_id null)', $unmappedTotal],
                ['Missing mappings', $missingMappings],
            ],
        );

        $this->info($dryRun ? 'Dry-run completed.' : 'Legacy Nat migration completed.');

        return self::SUCCESS;
    }

    protected function mapCementToNatPayload(Cement $cement): array
    {
        return [
            'nat_name' => $cement->cement_name ?: 'Nat',
            'photo' => $cement->photo,
            'brand' => $cement->brand,
            'sub_brand' => $cement->sub_brand,
            'code' => $cement->code,
            'color' => $cement->color,
            'package_unit' => $cement->package_unit,
            'package_weight_gross' => $cement->package_weight_gross,
            'package_weight_net' => $cement->package_weight_net,
            'package_volume' => $cement->package_volume,
            'store' => $cement->store,
            'address' => $cement->address,
            'store_location_id' => $cement->store_location_id,
            'package_price' => $cement->package_price,
            'price_unit' => $cement->price_unit,
            'comparison_price_per_kg' => $cement->comparison_price_per_kg,
        ];
    }
}
