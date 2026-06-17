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
            $table->string('email', 64)->default('');
            $table->string('phone', 32)->default('');
            $table->string('color', 7)->default('#000000');
            $table->string('address_city', 32)->default('');
            $table->string('address_postal_code', 6)->default('');
            $table->string('address_street', 32)->default('');
            $table->string('address_building_number', 8)->default('');
            $table->string('address_apartment_number', 8)->default('');
            $table->integer('order')->default(0);
            $table->boolean('archive')->default(false);
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
