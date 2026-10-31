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
        if (!Schema::hasTable('delivery_man_wallets')) {
            Schema::create('delivery_man_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_man_id')->unique();
                $table->decimal('cash_in_hand', 24, 2)->default(0.00);
                $table->decimal('total_collected_cash', 24, 2)->default(0.00);
                $table->decimal('total_settled_amount', 24, 2)->default(0.00);
                $table->unsignedInteger('collected_orders_count')->default(0);
                $table->timestamp('last_settlement_at')->nullable();
                $table->timestamps();

                $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('delivery_man_custody_transactions')) {
            Schema::create('delivery_man_custody_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id', 100)->unique();
                $table->unsignedBigInteger('delivery_man_id')->index();
                $table->string('transaction_type', 50)->index(); // 'cash_collection', 'custody_settlement'
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->decimal('amount', 24, 2)->default(0.00);
                $table->decimal('balance_before', 24, 2)->default(0.00);
                $table->decimal('balance_after', 24, 2)->default(0.00);
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->unsignedBigInteger('settled_by_admin_id')->nullable();
                $table->string('settlement_type', 30)->nullable(); // 'full', 'partial'
                $table->string('payment_method', 50)->nullable();
                $table->string('reference', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('delivery_man_id')->references('id')->on('delivery_men')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_man_custody_transactions');
        Schema::dropIfExists('delivery_man_wallets');
    }
};
