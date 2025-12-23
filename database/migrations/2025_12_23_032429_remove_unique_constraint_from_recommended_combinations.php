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
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // Drop foreign key first to release the index
            $table->dropForeign(['brick_id']);
            
            // Drop the unique index
            $table->dropUnique(['brick_id', 'type']);
            
            // Re-add foreign key
            $table->foreign('brick_id')
                  ->references('id')
                  ->on('bricks')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->unique(['brick_id', 'type']);
        });
    }
};
