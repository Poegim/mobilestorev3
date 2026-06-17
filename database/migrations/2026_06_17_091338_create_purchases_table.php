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
        Schema::create('purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_shop_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('added_timestamp');
            $table->unsignedTinyInteger('valid');
            $table->unsignedInteger('payment_method');
            $table->string('invoice_number', 32)->nullable();
            $table->unsignedInteger('purchase_contract_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
