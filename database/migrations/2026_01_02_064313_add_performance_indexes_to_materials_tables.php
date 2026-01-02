<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance indexes untuk optimize query speed
     * Target: 50-70% faster database queries
     */
    public function up(): void
    {
        // ============================================
        // BRICKS TABLE INDEXES
        // ============================================
        Schema::table('bricks', function (Blueprint $table) {
            // Index untuk search by brand (LIKE queries)
            $table->index('brand', 'idx_bricks_brand');

            // Index untuk filtering by type
            $table->index('type', 'idx_bricks_type');

            // Index untuk sorting by price
            $table->index('price_per_piece', 'idx_bricks_price');

            // Composite index untuk combined filters
            $table->index(['brand', 'type'], 'idx_bricks_brand_type');
        });

        // ============================================
        // CEMENTS TABLE INDEXES
        // ============================================
        Schema::table('cements', function (Blueprint $table) {
            // Index untuk search by brand
            $table->index('brand', 'idx_cements_brand');

            // Index untuk filtering by type
            $table->index('type', 'idx_cements_type');

            // Index untuk sorting by package_price
            $table->index('package_price', 'idx_cements_package_price');

            // Index untuk filtering by package_weight_net (validation queries)
            $table->index('package_weight_net', 'idx_cements_weight');

            // Composite index untuk combined filters
            $table->index(['brand', 'type'], 'idx_cements_brand_type');
        });

        // ============================================
        // SANDS TABLE INDEXES
        // ============================================
        Schema::table('sands', function (Blueprint $table) {
            // Index untuk search by brand
            $table->index('brand', 'idx_sands_brand');

            // Index untuk filtering by type
            $table->index('type', 'idx_sands_type');

            // Index untuk sorting by package_price
            $table->index('package_price', 'idx_sands_package_price');

            // Index untuk comparison_price_per_m3 (frequently used in validation & sorting)
            $table->index('comparison_price_per_m3', 'idx_sands_comparison_price');

            // Composite index untuk combined filters
            $table->index(['brand', 'type'], 'idx_sands_brand_type');
        });

        // ============================================
        // CATS TABLE INDEXES
        // ============================================
        Schema::table('cats', function (Blueprint $table) {
            // Index untuk search by brand
            $table->index('brand', 'idx_cats_brand');

            // Index untuk sorting by purchase_price
            $table->index('purchase_price', 'idx_cats_purchase_price');

            // Index untuk filtering by color_name
            $table->index('color_name', 'idx_cats_color_name');

            // Composite index untuk combined filters
            $table->index(['brand', 'color_name'], 'idx_cats_brand_color');
        });

        // ============================================
        // BRICK_CALCULATIONS TABLE INDEXES
        // ============================================
        Schema::table('brick_calculations', function (Blueprint $table) {
            // Index untuk sorting by created_at (log view, recent queries)
            $table->index('created_at', 'idx_brick_calc_created');

            // Index untuk filtering by total_material_cost
            $table->index('total_material_cost', 'idx_brick_calc_cost');

            // Index untuk filtering by wall_area (numeric search)
            $table->index('wall_area', 'idx_brick_calc_area');
        });

        // JSON index untuk work_type queries
        // MySQL 5.7+ supports virtual columns for JSON indexing
        // Create a virtual generated column first, then index it
        DB::statement('ALTER TABLE brick_calculations ADD work_type_virtual VARCHAR(50) AS (JSON_UNQUOTE(JSON_EXTRACT(calculation_params, "$.work_type"))) VIRTUAL');
        DB::statement('CREATE INDEX idx_brick_calc_work_type ON brick_calculations(work_type_virtual)');

        // ============================================
        // RECOMMENDED_COMBINATIONS TABLE INDEXES
        // ============================================
        Schema::table('recommended_combinations', function (Blueprint $table) {
            // Composite index untuk frequent query pattern:
            // WHERE work_type = ? AND type = ? AND is_active = 1
            $table->index(['work_type', 'type', 'is_active'], 'idx_rec_combo_work_type_active');

            // Index untuk filtering by brick_id
            $table->index('brick_id', 'idx_rec_combo_brick');

            // Index untuk sort_order
            $table->index('sort_order', 'idx_rec_combo_sort');

            // Composite index untuk cement-sand lookup
            $table->index(['cement_id', 'sand_id'], 'idx_rec_combo_cement_sand');
        });

        // ============================================
        // UNITS TABLE INDEXES
        // ============================================
        Schema::table('units', function (Blueprint $table) {
            // Index untuk search by code
            $table->index('code', 'idx_units_code');

            // Index untuk search by name
            $table->index('name', 'idx_units_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop BRICKS indexes
        Schema::table('bricks', function (Blueprint $table) {
            $table->dropIndex('idx_bricks_brand');
            $table->dropIndex('idx_bricks_type');
            $table->dropIndex('idx_bricks_price');
            $table->dropIndex('idx_bricks_brand_type');
        });

        // Drop CEMENTS indexes
        Schema::table('cements', function (Blueprint $table) {
            $table->dropIndex('idx_cements_brand');
            $table->dropIndex('idx_cements_type');
            $table->dropIndex('idx_cements_package_price');
            $table->dropIndex('idx_cements_weight');
            $table->dropIndex('idx_cements_brand_type');
        });

        // Drop SANDS indexes
        Schema::table('sands', function (Blueprint $table) {
            $table->dropIndex('idx_sands_brand');
            $table->dropIndex('idx_sands_type');
            $table->dropIndex('idx_sands_package_price');
            $table->dropIndex('idx_sands_comparison_price');
            $table->dropIndex('idx_sands_brand_type');
        });

        // Drop CATS indexes
        Schema::table('cats', function (Blueprint $table) {
            $table->dropIndex('idx_cats_brand');
            $table->dropIndex('idx_cats_purchase_price');
            $table->dropIndex('idx_cats_color_name');
            $table->dropIndex('idx_cats_brand_color');
        });

        // Drop BRICK_CALCULATIONS indexes
        Schema::table('brick_calculations', function (Blueprint $table) {
            $table->dropIndex('idx_brick_calc_created');
            $table->dropIndex('idx_brick_calc_cost');
            $table->dropIndex('idx_brick_calc_area');
        });

        // Drop JSON virtual column index and column
        DB::statement('DROP INDEX idx_brick_calc_work_type ON brick_calculations');
        DB::statement('ALTER TABLE brick_calculations DROP COLUMN work_type_virtual');

        // Drop RECOMMENDED_COMBINATIONS indexes
        Schema::table('recommended_combinations', function (Blueprint $table) {
            $table->dropIndex('idx_rec_combo_work_type_active');
            $table->dropIndex('idx_rec_combo_brick');
            $table->dropIndex('idx_rec_combo_sort');
            $table->dropIndex('idx_rec_combo_cement_sand');
        });

        // Drop UNITS indexes
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('idx_units_code');
            $table->dropIndex('idx_units_name');
        });
    }
};

