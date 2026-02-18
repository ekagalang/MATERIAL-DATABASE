<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bricks', function (Blueprint $table) {
            if (!Schema::hasColumn('bricks', 'package_type')) {
                $table->string('package_type')->default('eceran')->after('package_volume');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bricks', function (Blueprint $table) {
            if (Schema::hasColumn('bricks', 'package_type')) {
                $table->dropColumn('package_type');
            }
        });
    }
};
