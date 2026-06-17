<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy {--fresh : Truncate tables before import}';
    protected $description = 'Import data from the legacy MobileStore database';

    private int $imported = 0;

    public function handle(): int
    {
        $this->info('Connecting to legacy database...');

        try {
            DB::connection('legacy')->getPdo();
        } catch (\Exception $e) {
            $this->error('Cannot connect to legacy database: ' . $e->getMessage());
            return 1;
        }

        if ($this->option('fresh')) {
            $this->warn('Truncating all tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->tables() as $table) {
                DB::table($table)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->importTaxes();
        $this->importConditions();
        $this->importBrands();
        $this->importCategories();
        $this->importProducts();
        $this->importShops();
        $this->importContacts();
        $this->importUsers();
        $this->importUsersShops();
        $this->importItems();
        $this->importProductPrices();
        $this->importPurchases();
        $this->importPurchasesItems();
        $this->importSells();
        $this->importSellsItems();
        $this->importTransfers();
        $this->importTransfersItems();

        $this->newLine();
        $this->info("Import complete. {$this->imported} total records imported.");

        return 0;
    }

    private function tables(): array
    {
        return [
            'transfers_items', 'transfers',
            'sells_items', 'sells',
            'purchases_items', 'purchases',
            'product_prices', 'items',
            'users_shops', 'contacts',
            'shops', 'products', 'categories',
            'brands', 'conditions', 'taxes',
        ];
    }

    private function legacy(string $table)
    {
        return DB::connection('legacy')->table($table);
    }

    private function importChunked(string $label, string $legacyTable, string $newTable, callable $mapper): void
    {
        $count = 0;
        $this->legacy($legacyTable)->orderBy('id')->chunk(1000, function ($rows) use ($newTable, $mapper, &$count) {
            $batch = [];
            foreach ($rows as $row) {
                $mapped = $mapper((array) $row);
                if ($mapped) {
                    $batch[] = $mapped;
                }
            }
            if ($batch) {
                DB::table($newTable)->insert($batch);
                $count += count($batch);
            }
        });
        $this->imported += $count;
        $this->line("  ✓ {$label}: {$count} records");
    }

    private function importTaxes(): void
    {
        $this->importChunked('Taxes', 'taxes', 'taxes', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'percentage' => $r['percentage'],
        ]);
    }

    private function importConditions(): void
    {
        $this->importChunked('Conditions', 'conditions', 'conditions', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
        ]);
    }

    private function importBrands(): void
    {
        $this->importChunked('Brands', 'brands', 'brands', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
        ]);
    }

    private function importCategories(): void
    {
        $this->importChunked('Categories', 'categories', 'categories', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'parent_category_id' => $r['parent_category_id'],
        ]);
    }

    private function importProducts(): void
    {
        $this->importChunked('Products', 'products', 'products', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'parent_category_id' => $r['parent_category_id'],
            'brand_id' => $r['brand_id'],
            'gtin' => $r['gtin'],
        ]);
    }

    private function importShops(): void
    {
        $this->importChunked('Shops', 'shops', 'shops', fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'email' => $r['email'],
            'phone' => $r['phone'],
            'color' => $r['color'],
            'address_city' => $r['address_city'],
            'address_postal_code' => $r['address_postal_code'],
            'address_street' => $r['address_street'],
            'address_building_number' => $r['address_building_number'],
            'address_apartment_number' => $r['address_apartment_number'],
            'order' => $r['order'],
            'archive' => $r['archive'],
        ]);
    }

    private function importContacts(): void
    {
        $this->importChunked('Contacts', 'contacts', 'contacts', fn ($r) => [
            'id' => $r['id'],
            'name' => trim(($r['email'] ?: '') . ' ' . ($r['phone'] ?: '')),
            'identity_number' => $r['identity_number'] ?? '',
            'email' => $r['email'] ?? '',
            'phone' => $r['phone'] ?? '',
            'city' => $r['city'] ?? '',
            'postal_code' => is_string($r['postal_code']) ? substr($r['postal_code'], 0, 16) : '',
            'street' => $r['street'] ?? '',
            'notes' => $r['notes'] ?? null,
        ]);
    }

    private function importUsers(): void
    {
        $count = 0;
        $this->legacy('users')->orderBy('id')->chunk(100, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                $r = (array) $row;
                DB::table('users')->insert([
                    'id' => $r['id'],
                    'contact_id' => $r['contact_id'],
                    'privilege' => $r['privilege'],
                    'login' => $r['login'],
                    'name' => $r['login'],
                    'email' => null,
                    'password' => $r['password'],
                    'email_verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        });
        $this->imported += $count;
        $this->line("  ✓ Users: {$count} records");
    }

    private function importUsersShops(): void
    {
        $count = 0;
        $rows = $this->legacy('users_shops')->get();
        foreach ($rows as $row) {
            $r = (array) $row;
            DB::table('users_shops')->insert([
                'id' => $r['id'],
                'shop_id' => $r['shop_id'],
                'user_id' => $r['user_id'],
            ]);
            $count++;
        }
        $this->imported += $count;
        $this->line("  ✓ Users-Shops: {$count} records");
    }

    private function importItems(): void
    {
        $this->importChunked('Items', 'items', 'items', fn ($r) => [
            'id' => $r['id'],
            'parent_shop_id' => $r['parent_shop_id'],
            'product_id' => $r['product_id'],
            'status' => $r['status'],
            'feature_condition_id' => $r['feature_condition_id'],
            'feature_price' => $r['feature_price'],
            'barcode_scanned_timestamp' => $r['barcode_scanned_timestamp'],
            'displacement_timestamp' => $r['displacement_timestamp'],
        ]);
    }

    private function importProductPrices(): void
    {
        $this->importChunked('Product Prices', 'feature_condition_and_price_products', 'product_prices', fn ($r) => [
            'id' => $r['id'],
            'product_id' => $r['product_id'],
            'condition_id' => $r['condition_id'],
            'shop_id' => $r['shop_id'],
            'price' => $r['price'],
        ]);
    }

    private function importPurchases(): void
    {
        $this->importChunked('Purchases', 'purchases', 'purchases', fn ($r) => [
            'id' => $r['id'],
            'parent_shop_id' => $r['parent_shop_id'],
            'contact_id' => $r['contact_id'],
            'added_timestamp' => $r['added_timestamp'],
            'valid' => $r['valid'],
            'payment_method' => $r['payment_method'],
            'invoice_number' => $r['invoice_number'],
        ]);
    }

    private function importPurchasesItems(): void
    {
        $this->importChunked('Purchased Items', 'purchases_items', 'purchases_items', fn ($r) => [
            'id' => $r['id'],
            'purchase_id' => $r['purchase_id'],
            'item_id' => $r['item_id'],
            'tax_id' => $r['tax_id'],
            'price' => $r['price'],
        ]);
    }

    private function importSells(): void
    {
        $this->importChunked('Sells', 'sells', 'sells', fn ($r) => [
            'id' => $r['id'],
            'parent_shop_id' => $r['parent_shop_id'],
            'added_timestamp' => $r['added_timestamp'],
            'valid' => $r['valid'],
            'payment_method' => $r['payment_method'],
        ]);
    }

    private function importSellsItems(): void
    {
        $this->importChunked('Sold Items', 'sells_items', 'sells_items', fn ($r) => [
            'id' => $r['id'],
            'sell_id' => $r['sell_id'],
            'item_id' => $r['item_id'],
            'service_id' => $r['service_id'] ?? 0,
            'price' => $r['price'],
            'internal_cost' => $r['internal_cost'] ?? 0,
            'tax_id' => $r['tax_id'],
            'valid' => $r['valid'],
        ]);
    }

    private function importTransfers(): void
    {
        $this->importChunked('Transfers', 'transfers', 'transfers', fn ($r) => [
            'id' => $r['id'],
            'parent_shop_id' => $r['parent_shop_id'],
            'target_shop_id' => $r['target_shop_id'],
            'added_timestamp' => $r['added_timestamp'],
            'finished_timestamp' => $r['finished_timestamp'],
            'status' => $r['status'],
        ]);
    }

    private function importTransfersItems(): void
    {
        $this->importChunked('Transfer Items', 'transfers_items', 'transfers_items', fn ($r) => [
            'id' => $r['id'],
            'transfer_id' => $r['transfer_id'],
            'item_id' => $r['item_id'],
        ]);
    }
}