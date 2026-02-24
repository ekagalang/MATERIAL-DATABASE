<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_floors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('work_item_groupings', function (Blueprint $table) {
            $table->dropUnique('work_item_groupings_unique_combo');
            $table->foreignId('work_floor_id')->nullable()->after('formula_code')->constrained('work_floors')->nullOnDelete();
            $table->unique(
                ['formula_code', 'work_floor_id', 'work_area_id', 'work_field_id'],
                'work_item_groupings_unique_combo',
            );
        });
    }

    public function down(): void
    {
        Schema::table('work_item_groupings', function (Blueprint $table) {
            $table->dropUnique('work_item_groupings_unique_combo');
            $table->dropConstrainedForeignId('work_floor_id');
            $table->unique(['formula_code', 'work_area_id', 'work_field_id'], 'work_item_groupings_unique_combo');
        });

        Schema::dropIfExists('work_floors');
    }
};

