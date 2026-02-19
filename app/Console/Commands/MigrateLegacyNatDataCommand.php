<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyNatDataCommand extends Command
{
    protected $signature = 'nat:migrate-legacy
        {--dry-run : Preview migration without writing changes}
        {--limit=0 : Process only first N legacy rows (0 = all)}
        {--chunk=200 : Chunk size for processing}
        {--delete-source : Delete migrated rows from legacy nats table}
        {--force : Skip confirmation prompt}';

    protected $description = 'Move legacy nats rows into cements with material_kind=nat and repoint references';

    public function handle(): int
    {
        if (!Schema::hasTable('cements')) {
            $this->error('Table cements tidak tersedia.');
            return self::FAILURE;
        }

        if (!Schema::hasColumn('cements', 'material_kind') || !Schema::hasColumn('cements', 'nat_name')) {
            $this->error('Kolom cements.material_kind / cements.nat_name belum tersedia.');
            $this->line('Jalankan migration terbaru terlebih dulu.');
            return self::FAILURE;
        }

        if (!Schema::hasTable('nats')) {
            $this->info('Table nats tidak ditemukan. Tidak ada data legacy yang perlu dipindahkan.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(1, (int) $this->option('chunk'));
        $deleteSource = (bool) $this->option('delete-source');

        $query = DB::table('nats')->orderBy('id');
        $totalRows = (clone $query)->count();
        $total = $limit > 0 ? min($limit, $totalRows) : $totalRows;

        if ($total === 0) {
            $this->warn('Table nats kosong. Tidak ada data untuk dipindahkan.');
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $this->info('Rows in nats to process: ' . $total);
        $this->line('Mode: ' . ($dryRun ? 'DRY-RUN (no writes)' : 'WRITE'));
        $this->line('Chunk size: ' . $chunk);
        $this->line('Delete source rows: ' . ($deleteSource ? 'yes' : 'no'));

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Lanjutkan migrasi legacy nat dari table nats ke cements?', true)) {
                $this->warn('Migrasi dibatalkan.');
                return self::SUCCESS;
            }
        }

        $stats = [
            'processed' => 0,
            'created' => 0,
            'reused' => 0,
            'repointed_calculations' => 0,
            'repointed_recommendations' => 0,
            'repointed_availabilities' => 0,
            'source_deleted' => 0,
        ];

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $processor = function ($natRows) use (&$stats, $progress, $dryRun, $deleteSource): void {
            $idMap = [];
            $sourceIds = [];

            foreach ($natRows as $natRow) {
                $targetId = $this->findMatchingNatCementId($natRow);

                if (!$targetId) {
                    $payload = $this->mapNatRowToCementPayload($natRow);
                    $targetId = $dryRun ? 0 : (int) DB::table('cements')->insertGetId($payload);
                    $stats['created']++;
                } else {
                    if (!$dryRun) {
                        DB::table('cements')->where('id', $targetId)->update([
                            'material_kind' => 'nat',
                            'nat_name' => $natRow->nat_name ?: ($natRow->brand ?? 'Nat'),
                            'updated_at' => now(),
                        ]);
                    }
                    $stats['reused']++;
                }

                $idMap[(int) $natRow->id] = (int) $targetId;
                $sourceIds[] = (int) $natRow->id;
                $stats['processed']++;
                $progress->advance();
            }

            if ($dryRun) {
                $this->countRepointCandidates($idMap, $stats);
                if ($deleteSource) {
                    $stats['source_deleted'] += count($sourceIds);
                }
                return;
            }

            $this->repointReferences($idMap, $stats);

            if ($deleteSource && !empty($sourceIds)) {
                $stats['source_deleted'] += DB::table('nats')->whereIn('id', $sourceIds)->delete();
            }
        };

        if ($limit > 0) {
            $rows = $query->get();
            foreach ($rows->chunk($chunk) as $chunkRows) {
                $processor($chunkRows);
            }
        } else {
            $query->chunkById($chunk, function ($rows) use ($processor) {
                $processor($rows);
            }, 'id');
        }

        $progress->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Created in cements', $stats['created']],
                ['Reused existing cements rows', $stats['reused']],
                ['Repointed brick_calculations.nat_id', $stats['repointed_calculations']],
                ['Repointed recommended_combinations.nat_id', $stats['repointed_recommendations']],
                ['Repointed store_material_availabilities', $stats['repointed_availabilities']],
                ['Deleted rows from nats', $stats['source_deleted']],
            ],
        );

        $natInCements = DB::table('cements')->where('material_kind', 'nat')->count();
        $natInLegacyTable = Schema::hasTable('nats') ? DB::table('nats')->count() : 0;

        $this->table(
            ['Post-check', 'Count'],
            [
                ['Rows in cements (material_kind=nat)', $natInCements],
                ['Remaining rows in nats', $natInLegacyTable],
            ],
        );

        $this->info($dryRun ? 'Dry-run selesai.' : 'Migrasi legacy nat selesai.');

        return self::SUCCESS;
    }

    private function countRepointCandidates(array $idMap, array &$stats): void
    {
        foreach ($idMap as $oldId => $newId) {
            if ($newId <= 0 || $oldId === $newId) {
                continue;
            }

            if (Schema::hasTable('brick_calculations') && Schema::hasColumn('brick_calculations', 'nat_id')) {
                $stats['repointed_calculations'] += DB::table('brick_calculations')->where('nat_id', $oldId)->count();
            }

            if (
                Schema::hasTable('recommended_combinations') &&
                Schema::hasColumn('recommended_combinations', 'nat_id')
            ) {
                $stats['repointed_recommendations'] += DB::table('recommended_combinations')
                    ->where('nat_id', $oldId)
                    ->count();
            }

            if (
                Schema::hasTable('store_material_availabilities') &&
                Schema::hasColumn('store_material_availabilities', 'materialable_id') &&
                Schema::hasColumn('store_material_availabilities', 'materialable_type')
            ) {
                $stats['repointed_availabilities'] += DB::table('store_material_availabilities')
                    ->where('materialable_type', 'App\\Models\\Nat')
                    ->where('materialable_id', $oldId)
                    ->count();
            }
        }
    }

    private function repointReferences(array $idMap, array &$stats): void
    {
        foreach ($idMap as $oldId => $newId) {
            if ($newId <= 0 || $oldId === $newId) {
                continue;
            }

            if (Schema::hasTable('brick_calculations') && Schema::hasColumn('brick_calculations', 'nat_id')) {
                $stats['repointed_calculations'] += DB::table('brick_calculations')
                    ->where('nat_id', $oldId)
                    ->update(['nat_id' => $newId]);
            }

            if (
                Schema::hasTable('recommended_combinations') &&
                Schema::hasColumn('recommended_combinations', 'nat_id')
            ) {
                $stats['repointed_recommendations'] += DB::table('recommended_combinations')
                    ->where('nat_id', $oldId)
                    ->update(['nat_id' => $newId]);
            }

            if (
                Schema::hasTable('store_material_availabilities') &&
                Schema::hasColumn('store_material_availabilities', 'materialable_id') &&
                Schema::hasColumn('store_material_availabilities', 'materialable_type')
            ) {
                $stats['repointed_availabilities'] += DB::table('store_material_availabilities')
                    ->where('materialable_type', 'App\\Models\\Nat')
                    ->where('materialable_id', $oldId)
                    ->update(['materialable_id' => $newId]);
            }
        }
    }

    private function findMatchingNatCementId(object $natRow): ?int
    {
        $natName = $natRow->nat_name ?: ($natRow->brand ?? 'Nat');

        $query = DB::table('cements')->where('material_kind', 'nat');
        $query->where(function ($q) use ($natName) {
            $q->where('nat_name', $natName)->orWhere('cement_name', $natName);
        });

        $this->applyNullableWhere($query, 'type', $natRow->type ?? null);
        $this->applyNullableWhere($query, 'brand', $natRow->brand ?? null);
        $this->applyNullableWhere($query, 'sub_brand', $natRow->sub_brand ?? null);
        $this->applyNullableWhere($query, 'code', $natRow->code ?? null);
        $this->applyNullableWhere($query, 'color', $natRow->color ?? null);
        $this->applyNullableWhere($query, 'package_unit', $natRow->package_unit ?? null);
        $this->applyNullableWhere($query, 'package_weight_net', $natRow->package_weight_net ?? null);
        $this->applyNullableWhere($query, 'package_price', $natRow->package_price ?? null);
        $this->applyNullableWhere($query, 'store', $natRow->store ?? null);
        $this->applyNullableWhere($query, 'address', $natRow->address ?? null);

        if (Schema::hasColumn('cements', 'store_location_id')) {
            $this->applyNullableWhere($query, 'store_location_id', $natRow->store_location_id ?? null);
        }

        $id = $query->value('id');

        return $id ? (int) $id : null;
    }

    private function applyNullableWhere($query, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
    }

    private function mapNatRowToCementPayload(object $natRow): array
    {
        $natName = $natRow->nat_name ?: ($natRow->brand ?? 'Nat');

        $payload = [
            'cement_name' => $natName,
            'nat_name' => $natName,
            'material_kind' => 'nat',
            'type' => $natRow->type ?? null,
            'photo' => $natRow->photo ?? null,
            'brand' => $natRow->brand ?? null,
            'sub_brand' => $natRow->sub_brand ?? null,
            'code' => $natRow->code ?? null,
            'color' => $natRow->color ?? null,
            'package_unit' => $natRow->package_unit ?? null,
            'package_weight_gross' => $natRow->package_weight_gross ?? null,
            'package_weight_net' => $natRow->package_weight_net ?? null,
            'package_volume' => $natRow->package_volume ?? null,
            'store' => $natRow->store ?? null,
            'address' => $natRow->address ?? null,
            'store_location_id' => $natRow->store_location_id ?? null,
            'package_price' => $natRow->package_price ?? null,
            'price_unit' => $natRow->price_unit ?? null,
            'comparison_price_per_kg' => $natRow->comparison_price_per_kg ?? null,
            'created_at' => $natRow->created_at ?? now(),
            'updated_at' => $natRow->updated_at ?? now(),
        ];

        $columns = Schema::getColumnListing('cements');

        return array_filter(
            $payload,
            fn($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
