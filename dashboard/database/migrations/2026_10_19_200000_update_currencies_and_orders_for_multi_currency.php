<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update currencies table
        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                if (!Schema::hasColumn('currencies', 'symbol_position')) {
                    $table->string('symbol_position', 10)->default('right')->after('currency_symbol');
                }
                if (!Schema::hasColumn('currencies', 'decimal_digits')) {
                    $table->integer('decimal_digits')->default(2)->after('symbol_position');
                }
                if (!Schema::hasColumn('currencies', 'status')) {
                    $table->boolean('status')->default(0)->after('exchange_rate');
                }
                if (!Schema::hasColumn('currencies', 'is_base')) {
                    $table->boolean('is_base')->default(0)->after('status');
                }
            });

            // Modify exchange_rate precision to decimal(16, 6)
            try {
                DB::statement('ALTER TABLE `currencies` MODIFY `exchange_rate` DECIMAL(16, 6) DEFAULT 1.000000');
            } catch (\Exception $e) {
                // Ignore if already modified or unsupported
            }

            // Sync current base currency from business_settings
            $currentBaseCode = DB::table('business_settings')->where('key', 'currency')->value('value') ?? 'YER';
            
            // Mark base currency
            DB::table('currencies')->where('currency_code', $currentBaseCode)->update([
                'status' => 1,
                'is_base' => 1,
                'exchange_rate' => 1.000000,
            ]);

            // Activate SAR and USD with realistic regional exchange rates if they exist
            DB::table('currencies')->where('currency_code', 'SAR')->update([
                'status' => 1,
                'exchange_rate' => 140.000000,
            ]);

            DB::table('currencies')->where('currency_code', 'USD')->update([
                'status' => 1,
                'exchange_rate' => 530.000000,
            ]);
        }

        // 2. Update orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'currency_code')) {
                    $table->string('currency_code', 10)->nullable()->after('order_amount');
                }
                if (!Schema::hasColumn('orders', 'currency_exchange_rate')) {
                    $table->decimal('currency_exchange_rate', 16, 6)->nullable()->after('currency_code');
                }
                if (!Schema::hasColumn('orders', 'currency_amount')) {
                    $table->decimal('currency_amount', 24, 6)->nullable()->after('currency_exchange_rate');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                if (Schema::hasColumn('currencies', 'is_base')) {
                    $table->dropColumn('is_base');
                }
                if (Schema::hasColumn('currencies', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('currencies', 'decimal_digits')) {
                    $table->dropColumn('decimal_digits');
                }
                if (Schema::hasColumn('currencies', 'symbol_position')) {
                    $table->dropColumn('symbol_position');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'currency_amount')) {
                    $table->dropColumn('currency_amount');
                }
                if (Schema::hasColumn('orders', 'currency_exchange_rate')) {
                    $table->dropColumn('currency_exchange_rate');
                }
                if (Schema::hasColumn('orders', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }
    }
};
