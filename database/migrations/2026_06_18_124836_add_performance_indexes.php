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
        Schema::table('sells', function (Blueprint $table) {
            $table->index(['valid', 'parent_shop_id', 'created_at']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->index(['sell_id', 'valid']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['valid', 'parent_shop_id', 'created_at']);
        });

        Schema::table('purchases_items', function (Blueprint $table) {
            $table->index('purchase_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->index(['status', 'parent_shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropIndex(['valid', 'parent_shop_id', 'created_at']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->dropIndex(['sell_id', 'valid']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['valid', 'parent_shop_id', 'created_at']);
        });

        Schema::table('purchases_items', function (Blueprint $table) {
            $table->dropIndex('purchase_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['status', 'parent_shop_id']);
        });
    }
};
