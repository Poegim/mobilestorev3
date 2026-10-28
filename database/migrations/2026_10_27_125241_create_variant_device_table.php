<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_device', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('variant_id');
            $table->unsignedInteger('device_id');

            $table->unique(['variant_id', 'device_id']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_device');
    }
};