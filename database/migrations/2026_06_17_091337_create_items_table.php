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
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_shop_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('barcode_scanned_timestamp');
            $table->unsignedInteger('displacement_timestamp');
            $table->unsignedInteger('status');
            $table->unsignedTinyInteger('feature_box')->nullable();
            $table->unsignedInteger('feature_color_id')->nullable();
            $table->unsignedTinyInteger('feature_proof_of_purchase')->nullable();
            $table->text('feature_comment')->nullable();
            $table->unsignedInteger('feature_condition_id')->nullable();
            $table->unsignedInteger('feature_price')->nullable();
            $table->char('feature_imei', 32)->nullable();
            $table->unsignedInteger('feature_simlock_network_id')->nullable();
            $table->float('feature_memory')->unsigned()->nullable();
            $table->float('feature_storage')->unsigned()->nullable();
            $table->unsignedTinyInteger('feature_dual_sim');

            $table->index('parent_shop_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
