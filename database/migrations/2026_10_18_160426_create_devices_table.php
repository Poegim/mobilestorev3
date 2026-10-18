<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->string('model', 255);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'model']);
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
