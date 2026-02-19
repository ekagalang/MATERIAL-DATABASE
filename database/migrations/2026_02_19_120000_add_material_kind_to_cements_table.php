<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cements')) {
            return;
        }

        if (!Schema::hasColumn('cements', 'material_kind')) {
            Schema::table('cements', function (Blueprint $table) {
                $table->string('material_kind', 30)->default('cement')->after('type');
                $table->index('material_kind');
            });
        }

        DB::table('cements')
            ->whereNull('material_kind')
            ->orWhere('material_kind', '')
            ->update(['material_kind' => 'cement']);

        // Backfill legacy nat rows that historically lived in cements.
        DB::table('cements')
            ->where(function ($query) {
                $query
                    ->where('cement_name', 'Nat')
                    ->orWhereIn('type', ['Regular', 'Epoxy', 'Sanded', 'Non-Sanded']);
            })
            ->update(['material_kind' => 'nat']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('cements') || !Schema::hasColumn('cements', 'material_kind')) {
            return;
        }

        Schema::table('cements', function (Blueprint $table) {
            $table->dropIndex(['material_kind']);
            $table->dropColumn('material_kind');
        });
    }
};
