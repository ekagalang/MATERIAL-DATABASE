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
        Schema::create('nats', function (Blueprint $table) {
            $table->id();
            $table->string('nat_name')->nullable();
            $table->string('photo')->nullable();
            $table->string('brand')->nullable();
            $table->string('sub_brand')->nullable();
            $table->string('code')->nullable();
            $table->string('color')->nullable();

            $table->string('package_unit')->nullable();
            $table->decimal('package_weight_gross', 10, 2)->nullable();
            $table->decimal('package_weight_net', 10, 2)->nullable();
            $table->decimal('package_volume', 15, 6)->nullable();

            $table->string('store')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('store_location_id')->nullable()->constrained('store_locations')->nullOnDelete();

            $table->decimal('package_price', 15, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->decimal('comparison_price_per_kg', 15, 2)->nullable();

            // Mapping helper for staged migration from cements.type = 'Nat'
            $table->foreignId('legacy_cement_id')->nullable()->unique()->constrained('cements')->nullOnDelete();

            $table->timestamps();

            $table->index(['brand', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nats');
    }
};
