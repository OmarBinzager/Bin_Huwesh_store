<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $childTables = [
            'order_details',
            'order_partial_payments',
            'order_images',
            'order_areas',
            'order_change_amounts',
            'offline_payments',
            'delivery_histories',
        ];

        foreach ($childTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'order_id')) {
                DB::table($table)
                    ->whereNotIn('order_id', function ($query) {
                        $query->select('id')->from('orders');
                    })
                    ->delete();
            }
        }

        $maxOrderId = (int) (DB::table('orders')->max('id') ?? 0);
        $nextAutoIncrement = max(100001, $maxOrderId + 1);

        try {
            DB::statement("ALTER TABLE `orders` AUTO_INCREMENT = {$nextAutoIncrement}");
        } catch (\Throwable $e) {
            // Ignore if DB driver is not MySQL/MariaDB
        }
    }

    public function down(): void
    {
        // Irreversible cleanup of orphaned rows
    }
};
