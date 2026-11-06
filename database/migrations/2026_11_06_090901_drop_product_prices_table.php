<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_prices');
    }

    public function down(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('condition_id');
            $table->unsignedInteger('shop_id');
            $table->unsignedInteger('price');
            $table->timestamps();
        });
    }
};
