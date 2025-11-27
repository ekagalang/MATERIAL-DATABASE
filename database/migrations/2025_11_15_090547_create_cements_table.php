<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cements', function (Blueprint $table) {
            $table->id();
            $table->string('cement_name')->nullable(); // Nama Semen (auto-generated)
            $table->string('type')->nullable(); // Jenis (Semen, dll)
            $table->string('photo')->nullable(); // Path foto
            $table->string('brand')->nullable(); // Merek
            $table->string('sub_brand')->nullable(); // Sub Merek
            $table->string('code')->nullable(); // Code
            $table->string('color')->nullable(); // Warna

            // Kemasan
            $table->string('package_unit')->nullable(); // Satuan kemasan (Sak, Kg, dll)
            $table->decimal('package_weight_gross', 10, 2)->nullable(); // Berat kotor
            $table->decimal('package_weight_net', 10, 2)->nullable(); // Berat bersih

            // Dimensi Kemasan (untuk perhitungan kubik)
            $table->decimal('dimension_length', 10, 4)->nullable(); // Panjang kemasan (m)
            $table->decimal('dimension_width', 10, 4)->nullable(); // Lebar kemasan (m)
            $table->decimal('dimension_height', 10, 4)->nullable(); // Tinggi kemasan (m)
            $table->decimal('package_volume', 10, 6)->nullable(); // Volume kemasan (m³)

            // Toko
            $table->string('store')->nullable(); // Nama Toko
            $table->text('address')->nullable(); // Alamat Lengkap
            $table->string('short_address')->nullable(); // Alamat Singkat

            // Harga
            $table->decimal('package_price', 15, 2)->nullable(); // Harga per kemasan
            $table->string('price_unit')->nullable(); // Satuan harga
            $table->decimal('comparison_price_per_kg', 15, 2)->nullable(); // Harga komparasi per kg

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cements');
    }
};
