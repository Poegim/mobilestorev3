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
            $table->string('name', 255)->default('');
            $table->string('identity_number', 32)->default('');
            $table->string('email', 48)->default('');
            $table->string('phone', 16)->default('');
            $table->string('city', 32)->default('');
            $table->string('postal_code', 16)->default('');
            $table->string('street', 32)->default('');
            $table->text('notes')->nullable();
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
