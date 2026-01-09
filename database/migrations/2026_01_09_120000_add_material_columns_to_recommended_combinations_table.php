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
            $table->dropForeign(['brick_id']);
            $table->dropForeign(['cement_id']);
            $table->dropForeign(['sand_id']);
        });

        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->unsignedBigInteger('brick_id')->nullable()->change();
            $table->unsignedBigInteger('cement_id')->nullable()->change();
            $table->unsignedBigInteger('sand_id')->nullable()->change();
            $table->foreignId('cat_id')->nullable()->after('sand_id')->constrained('cats')->nullOnDelete();
            $table->foreignId('ceramic_id')->nullable()->after('cat_id')->constrained('ceramics')->nullOnDelete();
            $table->foreignId('nat_id')->nullable()->after('ceramic_id')->constrained('cements')->nullOnDelete();
        });

        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->foreign('brick_id')->references('id')->on('bricks')->nullOnDelete();
            $table->foreign('cement_id')->references('id')->on('cements')->nullOnDelete();
            $table->foreign('sand_id')->references('id')->on('sands')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->dropForeign(['brick_id']);
            $table->dropForeign(['cement_id']);
            $table->dropForeign(['sand_id']);
            $table->dropForeign(['cat_id']);
            $table->dropForeign(['ceramic_id']);
            $table->dropForeign(['nat_id']);
        });

        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->dropColumn(['cat_id', 'ceramic_id', 'nat_id']);
            $table->unsignedBigInteger('brick_id')->nullable(false)->change();
            $table->unsignedBigInteger('cement_id')->nullable(false)->change();
            $table->unsignedBigInteger('sand_id')->nullable(false)->change();
        });

        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->foreign('brick_id')->references('id')->on('bricks')->cascadeOnDelete();
            $table->foreign('cement_id')->references('id')->on('cements')->cascadeOnDelete();
            $table->foreign('sand_id')->references('id')->on('sands')->cascadeOnDelete();
        });
    }
};
