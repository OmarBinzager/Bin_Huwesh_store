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
        if (!Schema::hasTable('size_charts')) {
            Schema::create('size_charts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('garment_type')->nullable(); // علوي، سفلي، أحذية، فساتين، عام
                $table->string('unit')->default('سم'); // سم، بوصة، EU، UK، US
                $table->json('columns')->nullable(); // أسماء الأعمدة: المقاس، الصدر، الطول، الكتف، الخصر...
                $table->json('rows')->nullable(); // مصفوفة الصفوف بالقيم
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->json('product_ids')->nullable(); // المنتجات المربوطة خصيصاً بهذا الجدول
                $table->text('instructions')->nullable(); // تعليمات القياس
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('size_charts');
    }
};
