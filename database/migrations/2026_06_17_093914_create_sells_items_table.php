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
        Schema::create('sells_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sell_id');
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('service_id')->default(0);
            $table->unsignedInteger('price');
            $table->unsignedInteger('internal_cost')->default(0);
            $table->unsignedInteger('tax_id');
            $table->unsignedTinyInteger('valid')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sells_items');
    }
};
