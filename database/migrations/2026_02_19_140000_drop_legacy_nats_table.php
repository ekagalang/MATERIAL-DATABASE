<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nats')) {
            Schema::drop('nats');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nats')) {
            return;
        }

        Schema::create('nats', function (Blueprint $table) {
            $table->id();
            $table->string('nat_name')->nullable();
            $table->string('type')->nullable();
            $table->string('photo')->nullable();
            $table->string('brand')->nullable();
            $table->string('sub_brand')->nullable();
            $table->string('code')->nullable();
            $table->string('color')->nullable();

            $table->string('package_unit')->nullable();
            $table->decimal('package_weight_gross', 10, 2)->nullable();
            $table->decimal('package_weight_net', 10, 2)->nullable();
            $table->decimal('package_volume', 30, 15)->nullable();

            $table->string('store')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('store_location_id')->nullable()->constrained('store_locations')->nullOnDelete();

            $table->decimal('package_price', 15, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->decimal('comparison_price_per_kg', 15, 2)->nullable();

            $table->timestamps();

            $table->index(['brand', 'code']);
        });
    }
};
