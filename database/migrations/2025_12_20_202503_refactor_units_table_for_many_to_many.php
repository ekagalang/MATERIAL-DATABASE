<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Buat tabel pivot
        Schema::create('unit_material_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('material_type', 50);
            $table->timestamps();

            $table->unique(['unit_id', 'material_type']);
        });

        // 2. Migrasi Data
        $existingUnits = DB::table('units')->get();
        // Group units by code to find duplicates
        $groupedUnits = $existingUnits->groupBy('code');

        foreach ($groupedUnits as $code => $units) {
            // Ambil unit pertama sebagai master
            $masterUnit = $units->first();

            // Collect semua material types dari grup ini
            $materialTypes = $units->pluck('material_type')->unique()->filter();

            // Masukkan ke tabel pivot untuk master unit
            foreach ($materialTypes as $type) {
                DB::table('unit_material_types')->insert([
                    'unit_id' => $masterUnit->id,
                    'material_type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Hapus unit duplikat (selain master)
            $duplicatesIds = $units->pluck('id')->filter(fn($id) => $id != $masterUnit->id);
            if ($duplicatesIds->isNotEmpty()) {
                DB::table('units')->whereIn('id', $duplicatesIds)->delete();
            }
        }

        // 3. Modifikasi tabel units
        Schema::table('units', function (Blueprint $table) {
            // Drop unique constraint lama
            $table->dropUnique('units_code_material_type_unique');
            $table->dropIndex('units_material_type_index');

            // Drop kolom material_type
            $table->dropColumn('material_type');

            // Tambah unique constraint baru di code saja
            $table->unique('code');
        });
    }

    public function down(): void
    {
        // Revert changes (agak tricky karena data loss potensi di material_type kalau cuma 1 kolom)
        // Kita coba balikin sebisa mungkin

        Schema::table('units', function (Blueprint $table) {
            $table->string('material_type', 50)->nullable(); // nullable dulu
            $table->dropUnique(['code']);
        });

        // Kembalikan data dari pivot ke tabel utama
        // Ini akan create duplicate units lagi seperti sebelumnya
        $pivotData = DB::table('unit_material_types')->get();

        foreach ($pivotData as $pivot) {
            $unit = DB::table('units')->find($pivot->unit_id);
            if ($unit) {
                // Cek apakah unit ini sudah punya material_type diisi
                // Kalau sudah, kita harus clone unit ini untuk material_type yang baru

                // Cari apakah unit dengan kode ini dan material_type ini sudah ada (untuk mencegah duplikat saat loop)
                $exists = DB::table('units')
                    ->where('code', $unit->code)
                    ->where('material_type', $pivot->material_type)
                    ->exists();

                if (!$exists) {
                    // Cek apakah record asli (master) material_typenya masih kosong?
                    // Karena kita buat nullable, record master material_typenya NULL.
                    // Kita update record master untuk penggunaan pertama, lalu clone untuk berikutnya.

                    $masterIsEmpty = DB::table('units')->where('id', $unit->id)->whereNull('material_type')->exists();

                    if ($masterIsEmpty) {
                        DB::table('units')
                            ->where('id', $unit->id)
                            ->update(['material_type' => $pivot->material_type]);
                    } else {
                        // Clone unit
                        $newUnit = (array) $unit;
                        unset($newUnit['id']); // Remove ID
                        $newUnit['material_type'] = $pivot->material_type;
                        $newUnit['created_at'] = now();
                        $newUnit['updated_at'] = now();

                        DB::table('units')->insert($newUnit);
                    }
                }
            }
        }

        // Drop pivot table
        Schema::dropIfExists('unit_material_types');

        // Restore constraint
        Schema::table('units', function (Blueprint $table) {
            $table->string('material_type', 50)->nullable(false)->change(); // make required again
            $table->unique(['code', 'material_type'], 'units_code_material_type_unique');
            $table->index('material_type', 'units_material_type_index');
        });
    }
};
