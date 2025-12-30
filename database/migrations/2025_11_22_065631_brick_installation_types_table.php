<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brick_installation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // '1/2 Bata', '1 Bata', '1/4 Bata', 'Rollag'
            $table->string('code', 20)->unique(); // 'half', 'one', 'quarter', 'rollag'
            $table->text('description')->nullable(); // Deskripsi posisi bata

            // Volume adukan per M2 (dari Excel) - sesuai dengan ukuran bata KUO SHIN
            $table
                ->decimal('mortar_volume_per_m2', 8, 6)
                ->nullable()
                ->comment('Volume adukan per M2 (m³/M2) - sesuai Excel');

            // Waste factor untuk perhitungan volume adukan
            // Mencakup: shrinkage, spillage, waste, dan lapisan dasar
            // Untuk 1/2 Bata dengan tebal 1cm: waste_factor = 1.727273
            $table
                ->decimal('waste_factor', 8, 6)
                ->default(1.727273)
                ->comment('Faktor waste untuk volume adukan (shrinkage, spillage, dll)');

            // Dimensi yang terlihat (dari POV kita)
            $table->enum('visible_side_width', ['length', 'width', 'height']); // Sisi alas yang terlihat
            $table->enum('visible_side_height', ['length', 'width', 'height']); // Sisi tinggi yang terlihat

            // Orientasi bata
            $table->enum('orientation', ['horizontal_lying', 'horizontal_standing']);
            // horizontal_lying = tidur horizontal
            // horizontal_standing = berdiri horizontal

            // Jumlah bata per M2 (akan dihitung dari dimensi)
            $table->decimal('bricks_per_sqm', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brick_installation_types');
    }
};
