<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('nats') || !Schema::hasColumn('nats', 'legacy_cement_id')) {
            return;
        }

        try {
            Schema::table('nats', function (Blueprint $table) {
                $table->dropForeign(['legacy_cement_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key might already be absent in some environments.
        }

        try {
            Schema::table('nats', function (Blueprint $table) {
                $table->dropUnique('nats_legacy_cement_id_unique');
            });
        } catch (\Throwable $e) {
            // Unique index might already be absent in some environments.
        }

        Schema::table('nats', function (Blueprint $table) {
            $table->dropColumn('legacy_cement_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('nats') || Schema::hasColumn('nats', 'legacy_cement_id')) {
            return;
        }

        Schema::table('nats', function (Blueprint $table) {
            $table
                ->foreignId('legacy_cement_id')
                ->nullable()
                ->after('comparison_price_per_kg')
                ->unique()
                ->constrained('cements')
                ->nullOnDelete();
        });
    }
};
