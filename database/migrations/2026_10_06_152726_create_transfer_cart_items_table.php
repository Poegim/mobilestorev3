<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_cart_items', function (Blueprint $table) {
            $table->id();
            // Match legacy int unsigned keys (no FK constraints, like the rest of the schema)
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('parent_shop_id');
            $table->unsignedInteger('item_id');
            $table->timestamps();

            // One cart line per user + source shop + item
            $table->unique(['user_id', 'parent_shop_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_cart_items');
    }
};