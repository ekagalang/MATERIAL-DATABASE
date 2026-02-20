<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_item_groupings', function (Blueprint $table) {
            $table->id();
            $table->string('formula_code');
            $table->foreignId('work_area_id')->nullable()->constrained('work_areas')->nullOnDelete();
            $table->foreignId('work_field_id')->nullable()->constrained('work_fields')->nullOnDelete();
            $table->timestamps();

            $table->unique(['formula_code', 'work_area_id', 'work_field_id'], 'work_item_groupings_unique_combo');
            $table->index('formula_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_groupings');
    }
};

