<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(false)->after('gtin');
            $table->index('is_legacy');
        });

        // All existing products came from the old system
        DB::table('products')->update(['is_legacy' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_legacy']);
            $table->dropColumn('is_legacy');
        });
    }
};
