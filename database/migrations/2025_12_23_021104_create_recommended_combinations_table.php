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
        Schema::create('recommended_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brick_id')->constrained('bricks')->cascadeOnDelete();
            $table->foreignId('cement_id')->constrained('cements')->cascadeOnDelete();
            $table->foreignId('sand_id')->constrained('sands')->cascadeOnDelete();
            $table->string('type')->default('best'); // 'best', 'common', etc.
            $table->timestamps();

            // Ensure one recommendation per type per brick
            $table->unique(['brick_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommended_combinations');
    }
};
