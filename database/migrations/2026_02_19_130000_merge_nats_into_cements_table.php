<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cements')) {
            return;
        }

        $this->ensureNatNameColumnOnCements();

        $natToCementMap = $this->migrateNatRowsToCements();
        $this->backfillNatNamesFromCementNames();

        $this->repointNatForeignKey('brick_calculations', $natToCementMap, 'cements');
        $this->repointNatForeignKey('recommended_combinations', $natToCementMap, 'cements');
        $this->repointStoreAvailabilityIds($natToCementMap, 'cements');
    }

    public function down(): void
    {
        if (!Schema::hasTable('cements') || !Schema::hasTable('nats')) {
            return;
        }

        $cementToNatMap = $this->migrateNatRowsBackToNats();

        $this->repointNatForeignKey('brick_calculations', $cementToNatMap, 'nats');
        $this->repointNatForeignKey('recommended_combinations', $cementToNatMap, 'nats');
        $this->repointStoreAvailabilityIds($cementToNatMap, 'nats');

        if (Schema::hasColumn('cements', 'nat_name')) {
            Schema::table('cements', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_cements_nat_name');
                } catch (\Throwable $e) {
                    // Ignore when index does not exist.
                }

                $table->dropColumn('nat_name');
            });
        }
    }

    private function ensureNatNameColumnOnCements(): void
    {
        if (!Schema::hasColumn('cements', 'nat_name')) {
            Schema::table('cements', function (Blueprint $table) {
                $table->string('nat_name')->nullable()->after('cement_name');
            });
        }

        try {
            Schema::table('cements', function (Blueprint $table) {
                $table->index('nat_name', 'idx_cements_nat_name');
            });
        } catch (\Throwable $e) {
            // Ignore when index already exists or database cannot alter it.
        }
    }

    private function backfillNatNamesFromCementNames(): void
    {
        DB::table('cements')
            ->where('material_kind', 'nat')
            ->where(function ($query) {
                $query->whereNull('nat_name')->orWhere('nat_name', '');
            })
            ->update(['nat_name' => DB::raw('cement_name')]);
    }

    /**
     * @return array<int, int> old nat id => new/existing cement id
     */
    private function migrateNatRowsToCements(): array
    {
        if (!Schema::hasTable('nats')) {
            return [];
        }

        $map = [];
        $rows = DB::table('nats')->orderBy('id')->get();

        foreach ($rows as $row) {
            $natName = trim((string) ($row->nat_name ?? '')) !== '' ? $row->nat_name : 'Nat';
            $existingId = $this->findMatchingNatCementId($row, $natName);

            if (!$existingId) {
                $insertData = $this->filterCementColumns([
                    'cement_name' => $natName,
                    'nat_name' => $natName,
                    'type' => $row->type ?? null,
                    'material_kind' => 'nat',
                    'photo' => $row->photo ?? null,
                    'brand' => $row->brand ?? null,
                    'sub_brand' => $row->sub_brand ?? null,
                    'code' => $row->code ?? null,
                    'color' => $row->color ?? null,
                    'package_unit' => $row->package_unit ?? null,
                    'package_weight_gross' => $row->package_weight_gross ?? null,
                    'package_weight_net' => $row->package_weight_net ?? null,
                    'package_volume' => $row->package_volume ?? null,
                    'store' => $row->store ?? null,
                    'address' => $row->address ?? null,
                    'store_location_id' => $row->store_location_id ?? null,
                    'package_price' => $row->package_price ?? null,
                    'price_unit' => $row->price_unit ?? null,
                    'comparison_price_per_kg' => $row->comparison_price_per_kg ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);

                $existingId = (int) DB::table('cements')->insertGetId($insertData);
            } else {
                DB::table('cements')
                    ->where('id', $existingId)
                    ->update(
                        $this->filterCementColumns([
                            'material_kind' => 'nat',
                            'nat_name' => $natName,
                            'cement_name' => $natName,
                            'updated_at' => now(),
                        ]),
                    );
            }

            $map[(int) $row->id] = (int) $existingId;
        }

        return $map;
    }

    /**
     * @return array<int, int> cement nat id => nat id
     */
    private function migrateNatRowsBackToNats(): array
    {
        $map = [];

        $natCements = DB::table('cements')
            ->where('material_kind', 'nat')
            ->orderBy('id')
            ->get();

        foreach ($natCements as $row) {
            $natName = trim((string) ($row->nat_name ?? $row->cement_name ?? '')) !== ''
                ? ($row->nat_name ?? $row->cement_name)
                : 'Nat';

            $existingNatId = $this->findMatchingNatRowId($row, $natName);
            if (!$existingNatId) {
                $insertData = $this->filterNatColumns([
                    'nat_name' => $natName,
                    'type' => $row->type ?? null,
                    'photo' => $row->photo ?? null,
                    'brand' => $row->brand ?? null,
                    'sub_brand' => $row->sub_brand ?? null,
                    'code' => $row->code ?? null,
                    'color' => $row->color ?? null,
                    'package_unit' => $row->package_unit ?? null,
                    'package_weight_gross' => $row->package_weight_gross ?? null,
                    'package_weight_net' => $row->package_weight_net ?? null,
                    'package_volume' => $row->package_volume ?? null,
                    'store' => $row->store ?? null,
                    'address' => $row->address ?? null,
                    'store_location_id' => $row->store_location_id ?? null,
                    'package_price' => $row->package_price ?? null,
                    'price_unit' => $row->price_unit ?? null,
                    'comparison_price_per_kg' => $row->comparison_price_per_kg ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);

                $existingNatId = (int) DB::table('nats')->insertGetId($insertData);
            }

            $map[(int) $row->id] = (int) $existingNatId;
        }

        return $map;
    }

    private function findMatchingNatCementId(object $natRow, string $natName): ?int
    {
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

    private function findMatchingNatRowId(object $cementRow, string $natName): ?int
    {
        $query = DB::table('nats')->where('nat_name', $natName);

        $this->applyNullableWhere($query, 'type', $cementRow->type ?? null);
        $this->applyNullableWhere($query, 'brand', $cementRow->brand ?? null);
        $this->applyNullableWhere($query, 'sub_brand', $cementRow->sub_brand ?? null);
        $this->applyNullableWhere($query, 'code', $cementRow->code ?? null);
        $this->applyNullableWhere($query, 'color', $cementRow->color ?? null);
        $this->applyNullableWhere($query, 'package_unit', $cementRow->package_unit ?? null);
        $this->applyNullableWhere($query, 'package_weight_net', $cementRow->package_weight_net ?? null);
        $this->applyNullableWhere($query, 'package_price', $cementRow->package_price ?? null);
        $this->applyNullableWhere($query, 'store', $cementRow->store ?? null);
        $this->applyNullableWhere($query, 'address', $cementRow->address ?? null);

        if (Schema::hasColumn('nats', 'store_location_id')) {
            $this->applyNullableWhere($query, 'store_location_id', $cementRow->store_location_id ?? null);
        }

        $id = $query->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @param  array<int, int>  $idMap
     */
    private function repointNatForeignKey(string $tableName, array $idMap, string $targetTable): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'nat_id')) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['nat_id']);
            });
        } catch (\Throwable $e) {
            // Ignore when foreign key does not exist in this environment.
        }

        if (!empty($idMap)) {
            foreach ($idMap as $oldId => $newId) {
                DB::table($tableName)->where('nat_id', $oldId)->update(['nat_id' => $newId]);
            }
        }

        if ($targetTable === 'cements') {
            $validIds = DB::table('cements')->where('material_kind', 'nat')->pluck('id')->map(fn($id) => (int) $id)->all();
        } else {
            $validIds = DB::table('nats')->pluck('id')->map(fn($id) => (int) $id)->all();
        }

        if (empty($validIds)) {
            DB::table($tableName)->whereNotNull('nat_id')->update(['nat_id' => null]);
        } else {
            DB::table($tableName)->whereNotNull('nat_id')->whereNotIn('nat_id', $validIds)->update(['nat_id' => null]);
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($targetTable) {
                $table->foreign('nat_id')->references('id')->on($targetTable)->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Ignore when the database driver cannot alter foreign keys.
        }
    }

    /**
     * @param  array<int, int>  $idMap
     */
    private function repointStoreAvailabilityIds(array $idMap, string $targetTable): void
    {
        if (
            !Schema::hasTable('store_material_availabilities') ||
            !Schema::hasColumn('store_material_availabilities', 'materialable_id') ||
            !Schema::hasColumn('store_material_availabilities', 'materialable_type')
        ) {
            return;
        }

        if (!empty($idMap)) {
            foreach ($idMap as $oldId => $newId) {
                DB::table('store_material_availabilities')
                    ->where('materialable_type', 'App\\Models\\Nat')
                    ->where('materialable_id', $oldId)
                    ->update(['materialable_id' => $newId]);
            }
        }

        $validIds = $targetTable === 'cements'
            ? DB::table('cements')->where('material_kind', 'nat')->pluck('id')->map(fn($id) => (int) $id)->all()
            : DB::table('nats')->pluck('id')->map(fn($id) => (int) $id)->all();

        if (empty($validIds)) {
            DB::table('store_material_availabilities')
                ->where('materialable_type', 'App\\Models\\Nat')
                ->delete();
            return;
        }

        DB::table('store_material_availabilities')
            ->where('materialable_type', 'App\\Models\\Nat')
            ->whereNotIn('materialable_id', $validIds)
            ->delete();
    }

    private function applyNullableWhere($query, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
    }

    private function filterCementColumns(array $data): array
    {
        $columns = Schema::getColumnListing('cements');

        return array_filter(
            $data,
            fn($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function filterNatColumns(array $data): array
    {
        $columns = Schema::getColumnListing('nats');

        return array_filter(
            $data,
            fn($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }
};
