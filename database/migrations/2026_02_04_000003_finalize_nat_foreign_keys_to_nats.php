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
        $this->finalizeNatColumn('brick_calculations');
        $this->finalizeNatColumn('recommended_combinations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->restoreDualNatColumns('brick_calculations');
        $this->restoreDualNatColumns('recommended_combinations');
    }

    private function finalizeNatColumn(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'nat_id_v2')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'nat_id')) {
                try {
                    $table->dropForeign(['nat_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key does not exist.
                }
            }

            try {
                $table->dropForeign(['nat_id_v2']);
            } catch (\Throwable $e) {
                // Ignore if foreign key does not exist.
            }
        });

        if (Schema::hasColumn($tableName, 'nat_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('nat_id');
            });
        }

        if (!Schema::hasColumn($tableName, 'nat_id') && Schema::hasColumn($tableName, 'nat_id_v2')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('nat_id_v2', 'nat_id');
            });
        }

        if (Schema::hasColumn($tableName, 'nat_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('nat_id')->references('id')->on('nats')->nullOnDelete();
            });
        }
    }

    private function restoreDualNatColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'nat_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            try {
                $table->dropForeign(['nat_id']);
            } catch (\Throwable $e) {
                // Ignore if foreign key does not exist.
            }
        });

        if (!Schema::hasColumn($tableName, 'nat_id_v2')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('nat_id', 'nat_id_v2');
            });
        }

        if (!Schema::hasColumn($tableName, 'nat_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('nat_id')->nullable()->after('nat_id_v2')->constrained('cements')->nullOnDelete();
            });
        }

        Schema::table($tableName, function (Blueprint $table) {
            try {
                $table->foreign('nat_id_v2')->references('id')->on('nats')->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        });
    }
};
