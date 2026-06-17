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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('color', 7)->nullable();
            $table->integer('parent_category_id');
            $table->unsignedInteger('display_in_menu');
            $table->unsignedInteger('display_in_top_menu');
            $table->unsignedTinyInteger('frontend');
            $table->string('fa_icon', 16)->nullable();
            $table->unsignedInteger('display_in_side_menu');
            $table->unsignedInteger('display_in_backend_menu');
            $table->unsignedInteger('bagsoff_category_id');
            $table->integer('presta_id')->nullable();
            $table->boolean('presta')->default(false);
            $table->string('jpk', 32);

            $table->index('parent_category_id');
            $table->index('display_in_menu');
            $table->index('display_in_top_menu');
            $table->index('frontend');
            $table->index('display_in_side_menu');
            $table->index('display_in_backend_menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
