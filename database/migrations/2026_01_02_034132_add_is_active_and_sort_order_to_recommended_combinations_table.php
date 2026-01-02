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
            $table->boolean('is_active')->default(true)->after('type');
            $table->integer('sort_order')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'sort_order']);
        });
    }
};
