<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('material_settings', function (Blueprint $table) {
            $table->id();
            $table->string('material_type'); // 'brick', 'cat', 'cement', 'sand'
            $table->boolean('is_visible')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique('material_type');
        });

        // Insert default settings
        DB::table('material_settings')->insert([
            ['material_type' => 'brick', 'is_visible' => false, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['material_type' => 'cat', 'is_visible' => false, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['material_type' => 'cement', 'is_visible' => false, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['material_type' => 'sand', 'is_visible' => false, 'display_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_settings');
    }
};
