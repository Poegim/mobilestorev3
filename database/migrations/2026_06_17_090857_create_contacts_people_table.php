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
        Schema::create('contacts_people', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 128);
            $table->string('surname', 128);
            $table->unsignedTinyInteger('is_female');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts_people');
    }
};
