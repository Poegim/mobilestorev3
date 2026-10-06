<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable: legacy transfers have no author information.
        // hasColumn guards make the migration safe to re-run after a partial failure.
        Schema::table('transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('transfers', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('target_shop_id');
            }

            if (! Schema::hasColumn('transfers', 'finished_by')) {
                $table->unsignedInteger('finished_by')->nullable()->after('finished_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'finished_by']);
        });
    }
};