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
        Schema::create('shops', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('email', 64);
            $table->string('phone', 32);
            $table->string('color', 7);
            $table->string('address_city', 32);
            $table->string('address_postal_code', 6);
            $table->string('address_street', 32);
            $table->string('address_building_number', 8);
            $table->string('address_apartment_number', 8);
            $table->unsignedInteger('next_bill_ordinal');
            $table->string('contract_prefix', 16)->unique();
            $table->integer('next_contract_ordinal');
            $table->string('invoice_prefix', 16)->unique();
            $table->integer('next_invoice_ordinal');
            $table->string('invoice_contract_prefix', 16);
            $table->integer('next_invoice_contract_ordinal');
            $table->unsignedInteger('next_servicing_ordinal');
            $table->string('servicing_prefix', 16);
            $table->string('outer_servicing_prefix', 16);
            $table->unsignedInteger('next_outer_servicing_ordinal');
            $table->string('pro_invoice_prefix', 16);
            $table->unsignedInteger('next_pro_invoice_ordinal');
            $table->integer('order');
            $table->unsignedInteger('image_file_id');
            $table->unsignedInteger('html_id');
            $table->unsignedTinyInteger('frontend');
            $table->unsignedInteger('success_coefficient_timestamp');
            $table->integer('success_coefficient');
            $table->integer('success_coefficient_previous');
            $table->unsignedTinyInteger('archive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
