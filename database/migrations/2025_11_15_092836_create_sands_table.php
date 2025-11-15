<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sands', function (Blueprint $table) {
            $table->id();
            $table->string('sand_name')->nullable(); // Nama Pasir (auto-generated)
            $table->string('type')->nullable(); // Jenis (Pasir, dll)
            $table->string('photo')->nullable(); // Path foto
            $table->string('brand')->nullable(); // Merek
            
            // Kemasan - Dimensi untuk kalkulasi volume
            $table->decimal('dimension_length', 10, 2)->nullable(); // Panjang (m)
            $table->decimal('dimension_width', 10, 2)->nullable(); // Lebar (m)
            $table->decimal('dimension_height', 10, 2)->nullable(); // Tinggi (m)
            $table->decimal('package_volume', 10, 6)->nullable(); // Volume dalam m³ (hasil kalkulasi)
            
            // Toko
            $table->string('store')->nullable(); // Nama Toko
            $table->text('address')->nullable(); // Alamat Lengkap
            $table->string('short_address')->nullable(); // Alamat Singkat
            
            // Harga
            $table->decimal('package_price', 15, 2)->nullable(); // Harga per kemasan
            $table->decimal('comparison_price_per_m3', 15, 2)->nullable(); // Harga komparasi per m³
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sands');
    }
};