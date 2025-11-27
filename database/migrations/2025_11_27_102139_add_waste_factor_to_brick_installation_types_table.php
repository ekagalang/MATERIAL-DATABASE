<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brick_installation_types', function (Blueprint $table) {
            // Waste factor untuk perhitungan volume adukan
            // Mencakup: shrinkage, spillage, waste, dan lapisan dasar
            // Untuk 1/2 Bata dengan tebal 1cm: waste_factor = 1.727273
            $table->decimal('waste_factor', 8, 6)->after('mortar_volume_per_m2')->default(1.727273)->comment('Faktor waste untuk volume adukan (shrinkage, spillage, dll)');
        });
    }

    public function down(): void
    {
        Schema::table('brick_installation_types', function (Blueprint $table) {
            $table->dropColumn('waste_factor');
        });
    }
};
