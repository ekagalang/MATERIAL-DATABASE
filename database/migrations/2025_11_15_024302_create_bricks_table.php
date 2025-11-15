<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bricks', function (Blueprint $table) {
            $table->id();
            $table->string('material_name')->default('Bata'); // Selalu "Bata"
            $table->string('type')->nullable(); // Jenis bata (Merah, Press, Ringan, dll)
            $table->string('photo')->nullable(); // Path foto
            $table->string('brand')->nullable(); // Merek
            $table->string('form')->nullable(); // Bentuk (Persegi, Berlubang, dll)
            
            // Dimensi untuk kalkulasi volume
            $table->decimal('dimension_length', 10, 2)->nullable(); // Panjang (cm)
            $table->decimal('dimension_width', 10, 2)->nullable(); // Lebar (cm)
            $table->decimal('dimension_height', 10, 2)->nullable(); // Tinggi (cm)
            
            // Volume kemasan (hasil kalkulasi dari p x l x t)
            $table->decimal('package_volume', 10, 6)->nullable(); // Volume dalam m3
            
            // Toko
            $table->string('store')->nullable(); // Nama Toko
            $table->text('address')->nullable(); // Alamat Lengkap
            $table->string('short_address')->nullable(); // Alamat Singkat
            
            // Harga
            $table->decimal('price_per_piece', 15, 2)->nullable(); // Harga per buah
            $table->decimal('comparison_price_per_m3', 15, 2)->nullable(); // Harga komparasi per m3 (kalkulasi)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bricks');
    }
};