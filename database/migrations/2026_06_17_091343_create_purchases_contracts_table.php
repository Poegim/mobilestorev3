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
        Schema::create('purchases_contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ordinal');
            $table->unsignedInteger('added_timestamp');
            $table->text('html');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_contracts');
    }
};
