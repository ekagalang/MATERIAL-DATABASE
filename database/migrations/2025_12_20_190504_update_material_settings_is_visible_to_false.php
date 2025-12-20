<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update is_visible dari true ke false untuk semua material types
        DB::table('material_settings')
            ->whereIn('material_type', ['brick', 'cat', 'cement', 'sand'])
            ->update(['is_visible' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke true jika rollback
        DB::table('material_settings')
            ->whereIn('material_type', ['brick', 'cat', 'cement', 'sand'])
            ->update(['is_visible' => true]);
    }
};
