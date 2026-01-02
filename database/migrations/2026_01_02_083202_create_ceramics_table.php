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
        Schema::create('ceramics', function (Blueprint $table) {
            $table->id(); // No table

            // Identitas Material
            $table->string('material_name')->default('Keramik'); // Material (Default: Keramik)
            $table->string('type')->nullable(); // Jenis (Lantai, Dinding, Granit, dll)
            $table->string('photo')->nullable(); // Path foto
            $table->string('brand')->nullable(); // Merek
            $table->string('sub_brand')->nullable(); // Sub Merek
            $table->string('code')->nullable(); // Code
            $table->string('color')->nullable(); // Warna
            $table->string('form')->nullable(); // Bentuk (Persegi, Persegi Panjang)

            // Dimensi (Panjang x Lebar x Tebal)
            $table->decimal('dimension_length', 10, 2)->nullable(); // Panjang (cm/mm)
            $table->decimal('dimension_width', 10, 2)->nullable(); // Lebar (cm/mm)
            $table->decimal('dimension_thickness', 10, 2)->nullable(); // Tebal (cm/mm)

            // Data Kemasan, Volume & Luas
            $table->string('packaging')->nullable(); // Kemasan (Dus, Box, Ikat)
            $table->integer('pieces_per_package')->nullable(); // Volume (Isi per kemasan)
            $table->decimal('coverage_per_package', 10, 4)->nullable(); // Luas (m2 coverage per kemasan)

            // Data Toko & Lokasi (Mengikuti penamaan table bricks)
            $table->string('store')->nullable(); // Toko
            $table->text('address')->nullable(); // Alamat

            // Harga
            $table->decimal('price_per_package', 15, 2)->nullable(); // Harga / Kemasan
            $table->decimal('comparison_price_per_m2', 15, 2)->nullable(); // Harga Komparasi / Satuan Material (per m2)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ceramics');
    }
};
