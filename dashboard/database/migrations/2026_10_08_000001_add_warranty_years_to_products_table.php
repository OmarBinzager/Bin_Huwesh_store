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
        if (!Schema::hasColumn('products', 'warranty_years')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('warranty_years')->nullable()->default(null)->after('weight');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'warranty_years')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('warranty_years');
            });
        }
    }
};
