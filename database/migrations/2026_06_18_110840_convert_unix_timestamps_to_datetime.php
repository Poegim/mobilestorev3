<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamps();
        });
        DB::table('purchases')->whereNotNull('added_timestamp')->update([
            'created_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
            'updated_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
        ]);
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('added_timestamp');
        });

        // sells
        Schema::table('sells', function (Blueprint $table) {
            $table->timestamps();
        });
        DB::table('sells')->whereNotNull('added_timestamp')->update([
            'created_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
            'updated_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
        ]);
        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn('added_timestamp');
        });

        // transfers
        Schema::table('transfers', function (Blueprint $table) {
            $table->timestamps();
            $table->timestamp('finished_at')->nullable()->after('status');
        });
        DB::table('transfers')->where('added_timestamp', '>', 0)->update([
            'created_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
            'updated_at' => DB::raw('FROM_UNIXTIME(added_timestamp)'),
        ]);
        DB::table('transfers')->where('finished_timestamp', '>', 0)->update([
            'finished_at' => DB::raw('FROM_UNIXTIME(finished_timestamp)'),
        ]);
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['added_timestamp', 'finished_timestamp']);
        });

        // items
        Schema::table('items', function (Blueprint $table) {
            $table->timestamp('barcode_scanned_at')->nullable();
            $table->timestamp('displaced_at')->nullable();
            $table->timestamps();
        });
        DB::table('items')->where('barcode_scanned_timestamp', '>', 0)->update([
            'barcode_scanned_at' => DB::raw('FROM_UNIXTIME(barcode_scanned_timestamp)'),
        ]);
        DB::table('items')->where('displacement_timestamp', '>', 0)->update([
            'displaced_at' => DB::raw('FROM_UNIXTIME(displacement_timestamp)'),
        ]);
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['barcode_scanned_timestamp', 'displacement_timestamp']);
        });
    }
};