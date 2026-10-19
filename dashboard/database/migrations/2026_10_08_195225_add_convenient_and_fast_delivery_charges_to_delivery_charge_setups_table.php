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
        Schema::table('delivery_charge_setups', function (Blueprint $table) {
            $table->double('convenient_delivery_charge')->default(0)->after('fixed_delivery_charge');
            $table->double('fast_delivery_charge')->default(0)->after('convenient_delivery_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_charge_setups', function (Blueprint $table) {
            $table->dropColumn(['convenient_delivery_charge', 'fast_delivery_charge']);
        });
    }
};
