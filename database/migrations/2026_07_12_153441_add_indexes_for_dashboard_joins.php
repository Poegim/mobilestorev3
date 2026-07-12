<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some of these already exist in the imported schema — add only what's missing.
        $this->addIndexIfMissing('purchases_items', 'item_id'); // fixes the 20s margin query
        $this->addIndexIfMissing('sells_items', 'sell_id');
        $this->addIndexIfMissing('sells_items', 'item_id');
    }

    public function down(): void
    {
        // Only drop the index this migration is responsible for creating.
        // sells_items indexes pre-existed, so we leave them untouched.
        if ($this->indexExists('purchases_items', 'item_id')) {
            Schema::table('purchases_items', fn (Blueprint $t) => $t->dropIndex(['item_id']));
        }
    }

    private function addIndexIfMissing(string $table, string $column): void
    {
        if (! $this->indexExists($table, $column)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($column));
        }
    }

    private function indexExists(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn ($index) => $index['columns'] === [$column]);
    }
};