<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupLegacyNatDataCommand extends Command
{
    protected $signature = 'nat:cleanup-legacy
        {--dry-run : Preview cleanup without writing changes}
        {--include-unmapped : Also delete nats rows that have not been matched in cements}
        {--drop-table : Drop legacy nats table when empty after cleanup}
        {--force : Skip confirmation prompt}';

    protected $description = 'Cleanup legacy nats table after consolidation into cements material_kind=nat';

    public function handle(): int
    {
        if (!Schema::hasTable('nats')) {
            $this->info('Table nats tidak ditemukan. Tidak ada legacy data untuk dibersihkan.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('cements')) {
            $this->error('Table cements tidak tersedia.');
            return self::FAILURE;
        }

        if (!Schema::hasColumn('cements', 'material_kind') || !Schema::hasColumn('cements', 'nat_name')) {
            $this->error('Kolom cements.material_kind / cements.nat_name belum tersedia.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $includeUnmapped = (bool) $this->option('include-unmapped');
        $dropTable = (bool) $this->option('drop-table');

        $rows = DB::table('nats')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->warn('Table nats sudah kosong.');
            if ($dropTable) {
                if ($dryRun) {
                    $this->line('DRY-RUN: table nats akan di-drop karena kosong.');
                } else {
                    Schema::dropIfExists('nats');
                    $this->info('Table nats berhasil di-drop.');
                }
            }
            return self::SUCCESS;
        }

        $mappedIds = [];
        $unmappedIds = [];

        foreach ($rows as $row) {
            $match = $this->findMatchingNatCementId($row);
            if ($match) {
                $mappedIds[] = (int) $row->id;
            } else {
                $unmappedIds[] = (int) $row->id;
            }
        }

        $targetIds = $includeUnmapped ? $rows->pluck('id')->map(fn($id) => (int) $id)->all() : $mappedIds;

        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows in nats', $rows->count()],
                ['Matched in cements(material_kind=nat)', count($mappedIds)],
                ['Unmatched in cements', count($unmappedIds)],
                ['Rows to delete now', count($targetIds)],
            ],
        );

        $this->line('Mode: ' . ($dryRun ? 'DRY-RUN (no writes)' : 'WRITE'));
        $this->line('Delete scope: ' . ($includeUnmapped ? 'ALL rows in nats' : 'MATCHED rows only'));
        $this->line('Drop table if empty: ' . ($dropTable ? 'yes' : 'no'));

        if (!$includeUnmapped && !empty($unmappedIds)) {
            $this->warn('Ada row legacy yang belum termatch ke cements. Row ini akan dipertahankan.');
        }

        if (empty($targetIds)) {
            $this->info('Tidak ada row yang perlu dihapus.');
            return self::SUCCESS;
        }

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Lanjutkan cleanup legacy table nats?', false)) {
                $this->warn('Cleanup dibatalkan.');
                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        $deleted = DB::table('nats')->whereIn('id', $targetIds)->delete();

        // Remove stale polymorphic links for ids that no longer exist as Nat records in cements.
        if (
            Schema::hasTable('store_material_availabilities') &&
            Schema::hasColumn('store_material_availabilities', 'materialable_id') &&
            Schema::hasColumn('store_material_availabilities', 'materialable_type')
        ) {
            $validNatIds = DB::table('cements')->where('material_kind', 'nat')->pluck('id')->map(fn($id) => (int) $id)->all();
            $staleIds = array_values(array_diff($targetIds, $validNatIds));
            if (!empty($staleIds)) {
                DB::table('store_material_availabilities')
                    ->where('materialable_type', 'App\\Models\\Nat')
                    ->whereIn('materialable_id', $staleIds)
                    ->delete();
            }
        }

        $remaining = DB::table('nats')->count();
        if ($dropTable && $remaining === 0) {
            Schema::dropIfExists('nats');
        }

        $this->table(
            ['Post-check', 'Count'],
            [
                ['Deleted rows from nats', $deleted],
                ['Remaining rows in nats', $remaining],
                ['Rows in cements (material_kind=nat)', DB::table('cements')->where('material_kind', 'nat')->count()],
            ],
        );

        if ($dropTable && $remaining === 0) {
            $this->info('Cleanup selesai. Table nats di-drop karena sudah kosong.');
        } else {
            $this->info('Cleanup legacy nats selesai.');
        }

        return self::SUCCESS;
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
}
