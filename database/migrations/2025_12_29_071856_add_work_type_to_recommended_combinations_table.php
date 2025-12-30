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
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // 1. Add work_type column (unique constraint already dropped in previous migration)
            $table->string('work_type')->after('brick_id')->default('brick_half');

            // 2. Create new unique constraint with work_type
            $table->unique(['brick_id', 'work_type', 'type'], 'rec_brick_work_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // 1. Drop new unique constraint
            $table->dropUnique('rec_brick_work_type_unique');

            // 2. Drop work_type column
            $table->dropColumn('work_type');

            // 3. Restore old unique constraint
            $table->unique(['brick_id', 'type']);
        });
    }
};
