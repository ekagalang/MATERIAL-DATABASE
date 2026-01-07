<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            // Ceramic columns
            $table->foreignId('ceramic_id')->nullable()->constrained('ceramics')->nullOnDelete()->after('cat_total_cost');
            $table->decimal('ceramic_quantity', 10, 2)->nullable()->comment('Total pieces');
            $table->decimal('ceramic_packages', 10, 2)->nullable()->comment('Total dus');
            $table->decimal('ceramic_total_cost', 15, 2)->default(0);

            // Nat (Grout) columns - references cements table
            $table->foreignId('nat_id')->nullable()->constrained('cements')->nullOnDelete()->after('ceramic_total_cost');
            $table->decimal('nat_quantity', 10, 2)->nullable()->comment('Total packages');
            $table->decimal('nat_kg', 10, 2)->nullable();
            $table->decimal('nat_total_cost', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->dropForeign(['ceramic_id']);
            $table->dropForeign(['nat_id']);
            $table->dropColumn([
                'ceramic_id',
                'ceramic_quantity',
                'ceramic_packages',
                'ceramic_total_cost',
                'nat_id',
                'nat_quantity',
                'nat_kg',
                'nat_total_cost',
            ]);
        });
    }
};