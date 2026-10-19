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
        if (!Schema::hasColumn('products', 'purchase_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->double('purchase_price')->default(0)->after('price');
            });
        }

        if (!Schema::hasColumn('order_details', 'purchase_price')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->double('purchase_price')->default(0)->after('price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'purchase_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('purchase_price');
            });
        }

        if (Schema::hasColumn('order_details', 'purchase_price')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropColumn('purchase_price');
            });
        }
    }
};
