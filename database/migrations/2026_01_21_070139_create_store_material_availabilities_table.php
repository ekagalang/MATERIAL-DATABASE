<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_material_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_location_id')->constrained('store_locations')->onDelete('cascade');
            $table->unsignedBigInteger('materialable_id');
            $table->string('materialable_type');
            $table->timestamps();

            $table->index(['materialable_id', 'materialable_type'], 'sma_materialable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_material_availabilities');
    }
};
