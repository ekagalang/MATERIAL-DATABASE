<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20); // Kg, L, Galon, etc
            $table->string('material_type', 50); // cat, cement, sand, brick
            $table->string('name', 100); // Kilogram, Liter, etc
            $table->decimal('package_weight', 10, 2)->default(0); // Berat kemasan dalam Kg
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique constraint: code + material_type
            // Jadi bisa ada "Kg" untuk cat, cement, sand (masing-masing terpisah)
            $table->unique(['code', 'material_type'], 'units_code_material_type_unique');

            // Index untuk performa query
            $table->index('material_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
