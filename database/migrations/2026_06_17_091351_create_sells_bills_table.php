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
        Schema::create('sells_bills', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sell_id');
            $table->unsignedInteger('custom_invoice_id');
            $table->unsignedInteger('added_timestamp');
            $table->unsignedInteger('ordinal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sells_bills');
    }
};
