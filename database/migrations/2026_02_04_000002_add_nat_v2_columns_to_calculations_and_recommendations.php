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
        if (Schema::hasTable('brick_calculations') && !Schema::hasColumn('brick_calculations', 'nat_id_v2')) {
            Schema::table('brick_calculations', function (Blueprint $table) {
                $table->foreignId('nat_id_v2')->nullable()->after('nat_id')->constrained('nats')->nullOnDelete();
            });
        }

        if (
            Schema::hasTable('recommended_combinations') &&
            !Schema::hasColumn('recommended_combinations', 'nat_id_v2')
        ) {
            Schema::table('recommended_combinations', function (Blueprint $table) {
                $table->foreignId('nat_id_v2')->nullable()->after('nat_id')->constrained('nats')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('brick_calculations') && Schema::hasColumn('brick_calculations', 'nat_id_v2')) {
            Schema::table('brick_calculations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('nat_id_v2');
            });
        }

        if (
            Schema::hasTable('recommended_combinations') &&
            Schema::hasColumn('recommended_combinations', 'nat_id_v2')
        ) {
            Schema::table('recommended_combinations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('nat_id_v2');
            });
        }
    }
};
