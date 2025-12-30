<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // Drop foreign keys first (if they depend on the index)
            $table->dropForeign(['brick_id']);
            $table->dropForeign(['cement_id']);
            $table->dropForeign(['sand_id']);

            // Drop the unique constraint
            $table->dropUnique('rec_brick_work_type_unique');

            // Re-add foreign keys
            $table->foreign('brick_id')->references('id')->on('bricks')->cascadeOnDelete();
            $table->foreign('cement_id')->references('id')->on('cements')->cascadeOnDelete();
            $table->foreign('sand_id')->references('id')->on('sands')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(['brick_id', 'work_type', 'type'], 'rec_brick_work_type_unique');
        });
    }
};
