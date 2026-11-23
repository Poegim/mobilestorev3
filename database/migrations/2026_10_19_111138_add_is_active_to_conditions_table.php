<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        // Hide legacy WWW conditions
        DB::table('conditions')->whereIn('id', [4, 5])->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};