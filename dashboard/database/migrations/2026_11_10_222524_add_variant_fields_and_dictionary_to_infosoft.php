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
        // 1. Add variant columns to infosoft_product_mappings
        Schema::table('infosoft_product_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('infosoft_product_mappings', 'variation_type')) {
                $table->string('variation_type', 100)->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn('infosoft_product_mappings', 'color')) {
                $table->string('color', 100)->nullable()->after('variation_type');
            }
            if (!Schema::hasColumn('infosoft_product_mappings', 'size')) {
                $table->string('size', 100)->nullable()->after('color');
            }
        });

        // 2. Add variant grouping settings to infosoft_settings
        Schema::table('infosoft_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('infosoft_settings', 'variant_grouping_enabled')) {
                $table->boolean('variant_grouping_enabled')->default(true)->after('is_barcode');
            }
            if (!Schema::hasColumn('infosoft_settings', 'variant_separator')) {
                $table->string('variant_separator', 10)->default('-')->after('variant_grouping_enabled');
            }
        });

        // 3. Create normalization dictionary table
        Schema::create('infosoft_variant_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['color', 'size'])->default('color')->index();
            $table->string('raw_keyword', 100)->index();
            $table->string('normalized_name_ar', 100);
            $table->string('normalized_name_en', 100)->nullable();
            $table->string('hex_color', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infosoft_variant_dictionaries');

        Schema::table('infosoft_product_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('infosoft_product_mappings', 'variation_type')) {
                $table->dropColumn('variation_type');
            }
            if (Schema::hasColumn('infosoft_product_mappings', 'color')) {
                $table->dropColumn('color');
            }
            if (Schema::hasColumn('infosoft_product_mappings', 'size')) {
                $table->dropColumn('size');
            }
        });

        Schema::table('infosoft_settings', function (Blueprint $table) {
            if (Schema::hasColumn('infosoft_settings', 'variant_grouping_enabled')) {
                $table->dropColumn('variant_grouping_enabled');
            }
            if (Schema::hasColumn('infosoft_settings', 'variant_separator')) {
                $table->dropColumn('variant_separator');
            }
        });
    }
};
