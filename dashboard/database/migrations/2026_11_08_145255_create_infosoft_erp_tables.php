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
        // 1. Settings Table
        Schema::create('infosoft_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('api_base_url')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('database_name')->nullable();
            $table->string('default_warehouse')->default('01');
            $table->string('default_branch')->default('01');
            $table->string('sales_center')->default('01');
            $table->string('default_customer_code')->default('01001');
            $table->string('sales_person')->default('WEBSITE');
            $table->string('api_user')->default('API_USER');
            $table->string('default_invoice_type')->default('06'); // 06: B2C Cash Simplified, 04: B2B Credit
            $table->string('shipping_account')->nullable();
            $table->boolean('live_stock_check')->default(true);
            $table->boolean('is_barcode')->default(true);
            $table->integer('sync_days_lookback')->default(1);
            $table->integer('sync_frequency_minutes')->default(30);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_connection_test_at')->nullable();
            $table->string('last_connection_status')->nullable();
            $table->text('last_connection_message')->nullable();
            $table->timestamps();
        });

        // 2. Packing Units Table
        Schema::create('infosoft_packing_units', function (Blueprint $table) {
            $table->id();
            $table->string('pack_id', 50)->unique();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->decimal('default_pk_qty', 10, 2)->default(1.00);
            $table->timestamps();
        });

        // 3. Product Mappings Table
        Schema::create('infosoft_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned()->index();
            $table->string('erp_item_id', 50)->index();
            $table->string('erp_cmbkey', 50)->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('pack_name', 50)->nullable();
            $table->decimal('pack_qty', 10, 2)->default(1.00);
            $table->decimal('last_synced_price', 12, 2)->nullable();
            $table->decimal('last_synced_stock', 12, 2)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        // 4. Invoice & Return Audit Logs (Dead-Letter Queue Support)
        Schema::create('infosoft_invoice_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->unsigned()->index();
            $table->string('erp_invoice_id', 50)->nullable()->index();
            $table->string('type', 20)->default('invoice'); // invoice, return
            $table->string('status', 20)->default('pending'); // pending, success, failed
            $table->string('error_code', 30)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });

        // 5. Add ERP tracking fields to orders table if not exist
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'erp_invoice_id')) {
                $table->string('erp_invoice_id', 50)->nullable()->after('order_status')->index();
            }
            if (!Schema::hasColumn('orders', 'erp_sync_status')) {
                $table->string('erp_sync_status', 20)->default('pending')->after('erp_invoice_id');
            }
            if (!Schema::hasColumn('orders', 'erp_sync_error')) {
                $table->text('erp_sync_error')->nullable()->after('erp_sync_status');
            }
        });

        // 6. Add ERP tracking fields to products table if not exist
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'erp_item_id')) {
                $table->string('erp_item_id', 50)->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('products', 'erp_cmbkey')) {
                $table->string('erp_cmbkey', 50)->nullable()->after('erp_item_id')->index();
            }
            if (!Schema::hasColumn('products', 'erp_barcode')) {
                $table->string('erp_barcode', 100)->nullable()->after('erp_cmbkey')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infosoft_invoice_logs');
        Schema::dropIfExists('infosoft_product_mappings');
        Schema::dropIfExists('infosoft_packing_units');
        Schema::dropIfExists('infosoft_settings');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'erp_invoice_id')) {
                $table->dropColumn('erp_invoice_id');
            }
            if (Schema::hasColumn('orders', 'erp_sync_status')) {
                $table->dropColumn('erp_sync_status');
            }
            if (Schema::hasColumn('orders', 'erp_sync_error')) {
                $table->dropColumn('erp_sync_error');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'erp_item_id')) {
                $table->dropColumn('erp_item_id');
            }
            if (Schema::hasColumn('products', 'erp_cmbkey')) {
                $table->dropColumn('erp_cmbkey');
            }
            if (Schema::hasColumn('products', 'erp_barcode')) {
                $table->dropColumn('erp_barcode');
            }
        });
    }
};
