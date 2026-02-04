<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nats')) {
            return;
        }

        if (!Schema::hasColumn('nats', 'type')) {
            Schema::table('nats', function (Blueprint $table) {
                $table->string('type')->nullable()->after('nat_name');
            });
        }

        DB::table('nats')
            ->where(function ($query) {
                $query->whereNull('type')->orWhere('type', '');
            })
            ->update(['type' => 'Nat']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('nats') || !Schema::hasColumn('nats', 'type')) {
            return;
        }

        Schema::table('nats', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
