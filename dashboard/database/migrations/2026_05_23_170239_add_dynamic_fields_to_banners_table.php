<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDynamicFieldsToBannersTable extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            // نوع البانر: static, horizontal_slider, vertical_slider, grid
            $table->enum('display_type', ['single', 'carousel_horizontal', 'carousel_vertical', 'grid_2x2'])->default('single');

            // تحديد ترتيب ظهوره في الصفحة (من الأعلى للأسفل)
            $table->integer('sort_order')->default(0);

            // سلوك الضغط: link, none, product, category
            $table->enum('click_action', ['link', 'none', 'product', 'category'])->default('link');

            // الرابط إذا كان النوع link
            $table->string('external_link')->nullable();

            // القسم الذي يظهر فيه البانر (يمكن جعله متعدد الأقسام باستخدام JSON)
            $table->json('sections')->nullable(); // مثال: ["home_top", "home_middle", "product_page"]

            // دعم المنتجات المتعددة (للكاروسيل أو القريد)
            $table->json('product_ids')->nullable(); // لتخزين [1,5,7,9]
            $table->json('category_ids')->nullable(); // لتخزين [2,4,6]

            // نوع الترتيب داخل الكاروسيل
            $table->enum('scroll_direction', ['horizontal', 'vertical'])->default('horizontal');
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'display_type', 'sort_order', 'click_action',
                'external_link', 'sections', 'product_ids',
                'category_ids', 'scroll_direction'
            ]);
        });
    }
}
