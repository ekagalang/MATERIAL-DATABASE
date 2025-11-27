<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brick_installation_types', function (Blueprint $table) {
            // Volume adukan per m² (dari Excel) - sesuai dengan ukuran bata KUO SHIN
            $table->decimal('mortar_volume_per_m2', 8, 6)->after('description')->nullable()->comment('Volume adukan per m² (m³/m²) - sesuai Excel');
        });
    }

    public function down(): void
    {
        Schema::table('brick_installation_types', function (Blueprint $table) {
            $table->dropColumn('mortar_volume_per_m2');
        });
    }
};
