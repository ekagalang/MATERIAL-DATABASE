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
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->foreignId('cat_id')->nullable()->constrained('cats')->onDelete('set null');
            $table->decimal('cat_quantity', 10, 2)->nullable()->comment('Jumlah kemasan cat');
            $table->decimal('cat_kg', 10, 2)->nullable()->comment('Total berat cat dalam kg');
            $table->decimal('paint_liters', 10, 2)->nullable()->comment('Total volume cat dalam liter (jika ada)');

            // Optional: Add price columns if needed for detailed history
            $table->decimal('cat_price_per_package', 12, 2)->nullable();
            $table->decimal('cat_total_cost', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->dropForeign(['cat_id']);
            $table->dropColumn([
                'cat_id',
                'cat_quantity',
                'cat_kg',
                'paint_liters',
                'cat_price_per_package',
                'cat_total_cost',
            ]);
        });
    }
};
