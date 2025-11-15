<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cats', function (Blueprint $table) {
            $table->id();
            $table->string('cat_name'); // Nama Cat
            $table->string('type')->nullable(); // Jenis (CAT, dll)
            $table->string('photo')->nullable(); // Path foto
            $table->string('brand')->nullable(); // Merek
            $table->string('sub_brand')->nullable(); // Sub Merek
            $table->string('color_code')->nullable(); // Code Warna
            $table->string('color_name')->nullable(); // Nama Warna
            $table->string('form')->nullable(); // Bentuk
            
            // Kemasan
            $table->string('package_unit')->nullable(); // Satuan kemasan (Galon, Pail, dll)
            $table->decimal('package_weight_gross', 10, 2)->nullable(); // Berat kotor
            $table->decimal('package_weight_net', 10, 2)->nullable(); // Berat bersih (hasil kalkulasi)
            
            // Isi
            $table->decimal('volume', 10, 2)->nullable(); // Volume (opsional)
            $table->string('volume_unit')->nullable(); // Satuan volume (L, ml, dll)
            
            // Toko
            $table->string('store')->nullable(); // Nama Toko
            $table->text('address')->nullable(); // Alamat Lengkap
            $table->string('short_address')->nullable(); // Alamat Singkat
            
            // Harga
            $table->decimal('purchase_price', 15, 2)->nullable(); // Harga beli
            $table->string('price_unit')->nullable(); // Satuan harga
            $table->decimal('comparison_price_per_kg', 15, 2)->nullable(); // Harga komparasi per kg (hasil kalkulasi)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cats');
    }
};