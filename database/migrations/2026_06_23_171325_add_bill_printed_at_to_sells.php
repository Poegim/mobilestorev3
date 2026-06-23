<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->timestamp('bill_printed_at')->nullable()->after('payment_method');
        });

        // Backfill from sells_bills if the legacy table exists
        if (Schema::hasTable('sells_bills')) {
            DB::statement('
                UPDATE sells
                INNER JOIN sells_bills ON sells_bills.sell_id = sells.id
                SET sells.bill_printed_at = FROM_UNIXTIME(sells_bills.added_timestamp)
            ');
        }
    }

    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn('bill_printed_at');
        });
    }
};