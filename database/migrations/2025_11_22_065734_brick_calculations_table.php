<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brick_calculations', function (Blueprint $table) {
            $table->id();

            // Informasi project
            $table->string('project_name')->nullable();
            $table->text('notes')->nullable();

            // Dimensi dinding
            $table->decimal('wall_length', 10, 2);
            $table->decimal('wall_height', 10, 2);
            $table->decimal('wall_area', 10, 2);

            // Jenis pemasangan
            $table->foreignId('installation_type_id')->constrained('brick_installation_types')->onDelete('cascade');

            // Tebal adukan (cm)
            $table->decimal('mortar_thickness', 10, 2)->default(1.0);

            // Formula adukan yang digunakan
            $table->foreignId('mortar_formula_id')->constrained('mortar_formulas')->onDelete('cascade');

            // === CUSTOM RATIO (BARU) ===
            $table->decimal('custom_cement_ratio', 10, 4)->nullable();
            $table->decimal('custom_sand_ratio', 10, 4)->nullable();
            $table->decimal('custom_water_ratio', 10, 4)->nullable();
            $table->boolean('use_custom_ratio')->default(false);

            // === HASIL PERHITUNGAN ===

            // Bata
            $table->decimal('brick_quantity', 10, 2);
            $table->foreignId('brick_id')->nullable()->constrained('bricks')->onDelete('set null');
            $table->decimal('brick_price_per_piece', 15, 2)->nullable();
            $table->decimal('brick_total_cost', 15, 2)->nullable();

            // Volume adukan
            $table->decimal('mortar_volume', 10, 6);
            $table->decimal('mortar_volume_per_brick', 10, 6)->nullable();

            // Semen
            $table->decimal('cement_quantity_40kg', 10, 4)->nullable();
            $table->decimal('cement_quantity_50kg', 10, 4)->nullable();
            $table->decimal('cement_kg', 10, 2);
            $table->foreignId('cement_id')->nullable()->constrained('cements')->onDelete('set null');
            $table->decimal('cement_price_per_sak', 15, 2)->nullable();
            $table->decimal('cement_total_cost', 15, 2)->nullable();

            // Pasir
            $table->decimal('sand_sak', 10, 4)->nullable();
            $table->decimal('sand_m3', 10, 6);
            $table->decimal('sand_kg', 10, 2)->nullable();
            $table->foreignId('sand_id')->nullable()->constrained('sands')->onDelete('set null');
            $table->decimal('sand_price_per_m3', 15, 2)->nullable();
            $table->decimal('sand_total_cost', 15, 2)->nullable();

            // Air
            $table->decimal('water_liters', 10, 2);

            // Total biaya
            $table->decimal('total_material_cost', 15, 2)->nullable();

            // Metadata
            $table->json('calculation_params')->nullable();
            $table->timestamps();

            // Index untuk performa
            $table->index('created_at');
            $table->index('installation_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brick_calculations');
    }
};
