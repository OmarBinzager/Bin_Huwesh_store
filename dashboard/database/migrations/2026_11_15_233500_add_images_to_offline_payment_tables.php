<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offline_payment_methods') && !Schema::hasColumn('offline_payment_methods', 'image')) {
            Schema::table('offline_payment_methods', function (Blueprint $table) {
                $table->string('image')->nullable()->after('method_name');
            });
        }

        if (Schema::hasTable('offline_payments') && !Schema::hasColumn('offline_payments', 'receipt_image')) {
            Schema::table('offline_payments', function (Blueprint $table) {
                $table->string('receipt_image')->nullable()->after('payment_info');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('offline_payment_methods') && Schema::hasColumn('offline_payment_methods', 'image')) {
            Schema::table('offline_payment_methods', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }

        if (Schema::hasTable('offline_payments') && Schema::hasColumn('offline_payments', 'receipt_image')) {
            Schema::table('offline_payments', function (Blueprint $table) {
                $table->dropColumn('receipt_image');
            });
        }
    }
};
