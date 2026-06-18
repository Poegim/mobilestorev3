<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'taxes',
        'conditions',
        'brands',
        'categories',
        'products',
        'shops',
        'contacts',
        'product_prices',
        'purchases_items',
        'sells_items',
        'transfers_items',
        'users_shops',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropTimestamps();
            });
        }
    }
};