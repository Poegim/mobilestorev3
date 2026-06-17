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
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('parent_category_id');
            $table->unsignedInteger('brand_id');
            $table->unsignedInteger('html_id');
            $table->unsignedTinyInteger('frontend');
            $table->unsignedInteger('promo')->nullable();
            $table->unsignedInteger('display_in_menu');
            $table->string('gtin', 64)->nullable();
            $table->unsignedTinyInteger('google');
            $table->unsignedInteger('bagsoff_category_id');
            $table->integer('presta_id')->nullable();
            $table->string('jpk', 32);

            $table->index('parent_category_id');
            $table->index('brand_id');
            $table->index('display_in_menu');
            $table->index('promo');
            $table->index('frontend');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
