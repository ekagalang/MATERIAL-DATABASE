<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mortar_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Nama formula (e.g., "Adukan 1:4", "Adukan 1:5")
            $table->text('description')->nullable();
            
            // Rasio campuran (perbandingan volume)
            $table->decimal('cement_ratio', 10, 4); // Rasio semen (biasanya 1)
            $table->decimal('sand_ratio', 10, 4); // Rasio pasir (e.g., 4, 5, 6)
            $table->decimal('water_ratio', 10, 4)->nullable(); // Rasio air (opsional)
            
            // Faktor konversi untuk perhitungan
            // Berapa kg semen per m³ adukan
            $table->decimal('cement_kg_per_m3', 10, 2)->nullable();
            
            // Berapa m³ pasir per m³ adukan
            $table->decimal('sand_m3_per_m3', 10, 4)->nullable();
            
            // Berapa liter air per m³ adukan
            $table->decimal('water_liter_per_m3', 10, 2)->nullable();
            
            // Faktor pengembangan volume (expansion factor)
            // Karena adukan mengembang saat dicampur air
            $table->decimal('expansion_factor', 10, 4)->default(1.0);
            
            // Jenis semen yang disupport
            $table->enum('cement_bag_type', ['40kg', '50kg', 'both'])->default('both');
            
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortar_formulas');
    }
};