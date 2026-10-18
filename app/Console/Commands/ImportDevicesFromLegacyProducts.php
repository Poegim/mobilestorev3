<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDevicesFromLegacyProducts extends Command
{
    protected $signature = 'import:devices {--dry-run : Show what would be imported without inserting}';
    protected $description = 'Import devices from legacy products where category is "Urządzenia" (id=2 or parent=2)';

    public function handle(): int
    {
        // Collect all category IDs under the "devices" tree (category 2 + children)
        $categoryIds = DB::table('categories')
            ->where('id', 2)
            ->orWhere('parent_category_id', 2)
            ->pluck('id');

        $this->info("Categories in device tree: {$categoryIds->implode(', ')}");

        // Get unique brand + product name combinations
        $products = DB::table('products')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->whereIn('products.parent_category_id', $categoryIds)
            ->select('products.brand_id', 'brands.name as brand_name', 'products.name as product_name')
            ->distinct()
            ->orderBy('brands.name')
            ->orderBy('products.name')
            ->get();

        $this->info("Found {$products->count()} unique legacy products in device categories.");

        $created = 0;
        $skipped = 0;

        foreach ($products as $p) {
            // Strip brand name prefix from product name to get clean model
            $model = trim(preg_replace('/^' . preg_quote($p->brand_name, '/') . '\s*/i', '', $p->product_name));

            if ($model === '') {
                $this->warn("Skipping: {$p->brand_name} | {$p->product_name} → empty model after stripping brand");
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("{$p->brand_name} | {$p->product_name} → device: {$p->brand_name} {$model}");
                $created++;
                continue;
            }

            $device = Device::firstOrCreate(
                ['brand_id' => $p->brand_id, 'model' => $model],
                ['created_by' => null]
            );

            if ($device->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$created} devices would be created.");
        } else {
            $this->info("Done: {$created} created, {$skipped} already existed.");
        }

        return self::SUCCESS;
    }
}