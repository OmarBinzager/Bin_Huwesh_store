<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'try_on_enabled')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('try_on_enabled')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'try_on_enabled')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('try_on_enabled');
            });
        }
    }
};
