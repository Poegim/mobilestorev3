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
        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('identity_number', 32);
            $table->string('website', 64);
            $table->string('email', 48);
            $table->string('phone', 16);
            $table->string('mobile', 32);
            $table->string('country', 32);
            $table->string('city', 32);
            $table->text('postal_code');
            $table->string('street', 32);
            $table->string('building_number', 8);
            $table->string('apartment_number', 8);
            $table->text('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
