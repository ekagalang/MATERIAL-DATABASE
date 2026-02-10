<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $tables = ['bricks', 'cements', 'sands', 'ceramics', 'cats'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'store_location_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table
                        ->foreignId('store_location_id')
                        ->nullable()
                        ->after('address')
                        ->constrained('store_locations')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'store_location_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('store_location_id');
                });
            }
        }
    }
};
