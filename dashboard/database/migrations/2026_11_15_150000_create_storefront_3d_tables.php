<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('storefront_looks')) {
            Schema::create('storefront_looks', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 64)->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('published')->default(false);
                $table->string('title', 120);
                $table->string('subtitle', 255)->nullable();
                $table->string('image_path', 255);
                $table->integer('image_width')->default(0);
                $table->integer('image_height')->default(0);
                $table->string('image_alt', 255)->nullable();
                $table->decimal('scale', 4, 2)->default(1.00);
                $table->decimal('offset_x', 5, 1)->default(0.0);
                $table->decimal('offset_y', 5, 1)->default(0.0);
                $table->unsignedTinyInteger('whole_look_discount')->nullable();
                $table->string('href', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storefront_look_items')) {
            Schema::create('storefront_look_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('look_id')->constrained('storefront_looks')->cascadeOnDelete();
                $table->string('uid', 64);
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('label', 120);
                $table->string('category', 60)->default('');
                $table->decimal('hotspot_x', 5, 1)->default(50.0);
                $table->decimal('hotspot_y', 5, 1)->default(50.0);
                $table->integer('fallback_price')->default(0);
                $table->json('sizes')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storefront_virtual_fit_items')) {
            Schema::create('storefront_virtual_fit_items', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 64)->unique();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('category_key', 64);
                $table->string('category_name', 64);
                $table->string('name', 120);
                $table->string('description', 255)->nullable();
                $table->integer('fallback_price')->default(0);
                $table->json('sizes')->nullable();
                $table->json('colors');
                $table->boolean('visible')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storefront_shoe_shelf_items')) {
            Schema::create('storefront_shoe_shelf_items', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 64)->unique();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('name', 120);
                $table->string('description', 255)->nullable();
                $table->string('alt', 255)->nullable();
                $table->integer('fallback_price')->default(0);
                $table->json('sizes')->nullable();
                $table->json('colors');
                $table->boolean('visible')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storefront_rail_items')) {
            Schema::create('storefront_rail_items', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 64)->unique();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('name', 120);
                $table->string('kind', 32)->default('shirt');
                $table->string('color_hex', 16)->default('#18181b');
                $table->string('description', 255)->nullable();
                $table->json('details')->nullable();
                $table->string('front_image_path', 255);
                $table->string('back_image_path', 255);
                $table->string('side_image_path', 255);
                $table->integer('fallback_price')->default(0);
                $table->boolean('visible')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('storefront_category_cards')) {
            Schema::create('storefront_category_cards', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 64)->unique();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('slug', 64);
                $table->string('name', 120);
                $table->string('caption', 255)->nullable();
                $table->string('image_path', 255);
                $table->string('alt', 255)->nullable();
                $table->unsignedTinyInteger('focus_x')->default(50);
                $table->unsignedTinyInteger('focus_y')->default(50);
                $table->boolean('visible')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_category_cards');
        Schema::dropIfExists('storefront_rail_items');
        Schema::dropIfExists('storefront_shoe_shelf_items');
        Schema::dropIfExists('storefront_virtual_fit_items');
        Schema::dropIfExists('storefront_look_items');
        Schema::dropIfExists('storefront_looks');
    }
};
