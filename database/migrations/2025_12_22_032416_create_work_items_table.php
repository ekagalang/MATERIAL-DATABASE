<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Item Pekerjaan (mis: Pasang Bata Merah 1:4)
            $table->string('unit'); // Satuan (mis: m2, m3, titik)
            $table->decimal('price', 15, 2)->default(0); // Harga per satuan (Analisa Harga Satuan)
            $table->string('category')->nullable(); // Kategori (mis: Pekerjaan Dinding, Pekerjaan Lantai)
            $table->text('description')->nullable(); // Keterangan tambahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};