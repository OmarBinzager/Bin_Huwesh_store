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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'size_chart_id')) {
                $table->unsignedBigInteger('size_chart_id')->nullable()->after('brand_id');
            }
            if (!Schema::hasColumn('products', 'color_images')) {
                $table->json('color_images')->nullable()->after('image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'size_chart_id')) {
                $table->dropColumn('size_chart_id');
            }
            if (Schema::hasColumn('products', 'color_images')) {
                $table->dropColumn('color_images');
            }
        });
    }
};
