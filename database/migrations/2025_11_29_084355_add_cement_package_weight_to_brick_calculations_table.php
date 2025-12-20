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
        Schema::table('brick_calculations', function (Blueprint $table) {
            // Add column to store the actual cement package weight used
            $table->decimal('cement_package_weight', 10, 2)->nullable()->after('cement_kg');

            // Add column for generic cement quantity in sak
            $table->decimal('cement_quantity_sak', 10, 4)->nullable()->after('cement_package_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->dropColumn(['cement_package_weight', 'cement_quantity_sak']);
        });
    }
};
