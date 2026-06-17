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
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_shop_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('status');
            $table->unsignedInteger('feature_condition_id')->nullable();
            $table->unsignedInteger('feature_price')->nullable();
            $table->unsignedInteger('barcode_scanned_timestamp')->default(0);
            $table->unsignedInteger('displacement_timestamp')->default(0);

            $table->index('parent_shop_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
