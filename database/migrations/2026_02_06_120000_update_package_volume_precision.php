<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bricks', function (Blueprint $table) {
            $table->decimal('package_volume', 30, 15)->nullable()->change();
        });

        Schema::table('sands', function (Blueprint $table) {
            $table->decimal('package_volume', 30, 15)->nullable()->change();
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->decimal('package_volume', 30, 15)->nullable()->change();
        });

        Schema::table('nats', function (Blueprint $table) {
            $table->decimal('package_volume', 30, 15)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bricks', function (Blueprint $table) {
            $table->decimal('package_volume', 10, 6)->nullable()->change();
        });

        Schema::table('sands', function (Blueprint $table) {
            $table->decimal('package_volume', 10, 6)->nullable()->change();
        });

        Schema::table('cements', function (Blueprint $table) {
            $table->decimal('package_volume', 15, 6)->nullable()->change();
        });

        Schema::table('nats', function (Blueprint $table) {
            $table->decimal('package_volume', 15, 6)->nullable()->change();
        });
    }
};
