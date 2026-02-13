<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->text('project_address')->nullable()->after('notes');
            $table->decimal('project_latitude', 10, 7)->nullable()->after('project_address');
            $table->decimal('project_longitude', 10, 7)->nullable()->after('project_latitude');
            $table->string('project_place_id')->nullable()->after('project_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->dropColumn([
                'project_address',
                'project_latitude',
                'project_longitude',
                'project_place_id',
            ]);
        });
    }
};
