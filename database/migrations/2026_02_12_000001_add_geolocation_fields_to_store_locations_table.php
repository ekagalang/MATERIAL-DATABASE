<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('province');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('place_id')->nullable()->after('longitude');
            $table->text('formatted_address')->nullable()->after('place_id');
            $table->decimal('service_radius_km', 8, 2)->nullable()->after('formatted_address');

            $table->index(['latitude', 'longitude'], 'store_locations_lat_lng_index');
            $table->index('service_radius_km', 'store_locations_service_radius_index');
        });
    }

    public function down(): void
    {
        Schema::table('store_locations', function (Blueprint $table) {
            $table->dropIndex('store_locations_lat_lng_index');
            $table->dropIndex('store_locations_service_radius_index');
            $table->dropColumn([
                'latitude',
                'longitude',
                'place_id',
                'formatted_address',
                'service_radius_km',
            ]);
        });
    }
};
