<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('material_calculations', function (Blueprint $table) {
            $table->id();

            // Informasi project
            $table->string('project_name')->nullable();
            $table->text('notes')->nullable();

            // Jenis pekerjaan (work type)
            // Contoh: 'BrickHalfInstallation', 'BrickOneInstallation', 'PlasterWork', etc.
            $table->string('work_type', 100);

            // Parameter input dalam JSON format
            // Menyimpan semua input parameter yang diberikan user
            // Contoh untuk brick: {wall_length, wall_height, mortar_thickness, installation_type_id, etc.}
            $table->json('input_params');

            // Hasil perhitungan dalam JSON format
            // Menyimpan semua hasil kalkulasi material
            // Contoh: {total_bricks, cement_kg, cement_sak, sand_m3, water_liters, etc.}
            $table->json('calculation_results');

            // Trace step-by-step dalam JSON format
            // Menyimpan detail langkah-langkah perhitungan untuk debugging/audit
            // Struktur: [{step, title, formula, calculations, explanation}, ...]
            $table->json('trace_steps')->nullable();

            // Timestamp untuk kapan calculation disimpan
            $table->timestamp('saved_at')->nullable();

            // User yang menyimpan (untuk multi-user support di masa depan)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Index untuk performa
            $table->index('work_type');
            $table->index('created_at');
            $table->index('saved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_calculations');
    }
};
