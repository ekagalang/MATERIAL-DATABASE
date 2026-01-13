<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['bricks', 'cats', 'cements', 'sands', 'materials'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'address') || !Schema::hasColumn($table, 'short_address')) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) {
                    $query->whereNull('address')->orWhere('address', '');
                })
                ->whereNotNull('short_address')
                ->where('short_address', '!=', '')
                ->update(['address' => DB::raw('short_address')]);
        }
    }

    public function down(): void
    {
        // No rollback for data migration.
    }
};
