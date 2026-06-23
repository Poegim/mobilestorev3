<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportSellsBills extends Command
{
    protected $signature = 'import:sells-bills';
    protected $description = 'Backfill sells.bill_printed_at from legacy sells_bills table';

    public function handle(): int
    {
        if (! Schema::connection('legacy')->hasTable('sells_bills')) {
            $this->error('Legacy sells_bills table not found.');
            return 1;
        }

        $legacyDb = DB::connection('legacy')->getDatabaseName();
        $total = DB::connection('legacy')->table('sells_bills')->count();

        $this->info("Backfilling bill_printed_at from {$total} legacy sells_bills (single UPDATE)...");

        $count = DB::update("
            UPDATE sells
            INNER JOIN {$legacyDb}.sells_bills ON sells_bills.sell_id = sells.id
            SET sells.bill_printed_at = FROM_UNIXTIME(sells_bills.added_timestamp)
            WHERE sells.bill_printed_at IS NULL
              AND sells_bills.added_timestamp > 0
        ");

        $this->info("Done. Updated {$count} sells.");

        return 0;
    }
}