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
        Schema::table('cements', function (Blueprint $table) {
            $table->dropColumn(['dimension_length', 'dimension_width', 'dimension_height']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cements', function (Blueprint $table) {
            $table->decimal('dimension_length', 8, 2)->nullable();
            $table->decimal('dimension_width', 8, 2)->nullable();
            $table->decimal('dimension_height', 8, 2)->nullable();
        });
    }
};
