<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'variant_mode')) {
                $table->string('variant_mode', 32)->default('standard')->after('variations');
            }
            if (!Schema::hasColumn('products', 'erp_total_stock')) {
                $table->integer('erp_total_stock')->nullable()->after('total_stock');
            }
        });

        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'variant_image')) {
                $table->string('variant_image', 255)->nullable()->after('variation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variant_mode', 'erp_total_stock']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('variant_image');
        });
    }
};
