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
            $table->index('parent_shop_id');
            $table->index('added_timestamp');
            $table->index('valid');
            $table->index(['parent_shop_id', 'valid', 'added_timestamp']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->index('sell_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropIndex(['parent_shop_id']);
            $table->dropIndex(['added_timestamp']);
            $table->dropIndex(['valid']);
            $table->dropIndex(['parent_shop_id', 'valid', 'added_timestamp']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->dropIndex(['sell_id']);
            $table->dropIndex(['item_id']);
        });
    }
};
