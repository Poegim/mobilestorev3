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
            $table->index('created_at');
            $table->index(['valid', 'created_at']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->index(['valid', 'item_id']);
            $table->index(['valid', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['valid', 'created_at']);
        });

        Schema::table('sells_items', function (Blueprint $table) {
            $table->dropIndex(['valid', 'item_id']);
            $table->dropIndex(['valid', 'service_id']);
        });
    }
};
