<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ceramics', function (Blueprint $table) {
            $table->string('surface')->nullable()->after('form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ceramics', function (Blueprint $table) {
            $table->dropColumn('surface');
        });
    }
};
