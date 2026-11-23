<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Device;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedProductsFromLegacy extends Command
{
    protected $signature = 'seed:products-from-legacy
        {--limit=5000 : Number of top products to process}
        {--min-items=3 : Minimum item count to include}
        {--years=2 : Only products purchased within last N years}
        {--dry-run : Show what would be created without inserting}';

    protected $description = 'Create new catalog products from most popular legacy products (by recent purchases)';

    private array $deviceBrands = [
        'Apple', 'APPLE', 'Samsung', 'Huawei', 'Xiaomi', 'Motorola', 'Sony',
        'LG', 'Nokia', 'Oppo', 'Realme', 'OnePlus', 'Honor',
        'Kruger&Matz', 'HTC', 'Lenovo', 'Alcatel', 'ZTE', 'Vivo',
        'Google', 'Nothing', 'Poco', 'Infinix', 'Tecno', 'TCL',
    ];

    private int $created = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $minItems = (int) $this->option('min-items');
        $years = (int) $this->option('years');
        $dryRun = $this->option('dry-run');

        $legacyProducts = DB::table('items')
            ->join('purchases_items', 'items.id', '=', 'purchases_items.item_id')
            ->join('purchases', 'purchases_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('categories', 'products.parent_category_id', '=', 'categories.id')
            ->where('purchases.created_at', '>=', now()->subYears($years))
            ->select(
                'products.id as legacy_id',
                'products.name as raw_name',
                'products.brand_id',
                'products.parent_category_id',
                'brands.name as brand_name',
                'categories.name as category_name',
                DB::raw('COUNT(*) as item_count')
            )
            ->groupBy('products.id', 'products.name', 'products.brand_id',
                       'products.parent_category_id', 'brands.name', 'categories.name')
            ->having('item_count', '>=', $minItems)
            ->orderByDesc('item_count')
            ->limit($limit)
            ->get();

        $total = $legacyProducts->count();
        $this->info("Found {$total} legacy products purchased in last {$years} years with >= {$minItems} items.");
        $this->newLine();

        foreach ($legacyProducts as $i => $lp) {
            $parsed = $this->parse($lp->brand_name, $lp->raw_name);
            $deviceStr = $this->formatDevices($parsed['devices']);

            $num = $i + 1;
            $prefix = sprintf("[%4d/%d] %5d items | %-15s", $num, $total, $lp->item_count, $lp->brand_name);

            if ($dryRun) {
                $this->line("{$prefix} | <info>{$parsed['name']}</info> | {$deviceStr}");
                $this->created++;
                continue;
            }

            // Check duplicate
            $existingNew = Product::current()
                ->where('brand_id', $lp->brand_id)
                ->where('name', $parsed['name'])
                ->get();

            $deviceIds = $this->resolveDeviceIds($parsed['devices']);
            $sortedIds = collect($deviceIds)->sort()->values()->all();

            $isDuplicate = false;
            foreach ($existingNew as $ep) {
                $epIds = $ep->devices()->pluck('devices.id')->sort()->values()->all();
                if ($epIds === $sortedIds) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                $this->line("{$prefix} | <comment>SKIP</comment> {$parsed['name']} | {$deviceStr}");
                $this->skipped++;
                continue;
            }

            try {
                $product = Product::create([
                    'name' => $parsed['name'],
                    'brand_id' => $lp->brand_id,
                    'parent_category_id' => $lp->parent_category_id,
                    'is_legacy' => false,
                    'created_by' => null,
                ]);

                if (!empty($sortedIds)) {
                    $product->devices()->sync($sortedIds);
                }

                $this->line("{$prefix} | <info>OK</info>    {$parsed['name']} | {$deviceStr}");
                $this->created++;
            } catch (\Exception $e) {
                $this->line("{$prefix} | <error>FAIL</error>  {$parsed['name']} — {$e->getMessage()}");
                $this->failed++;
            }
        }

        $this->newLine();
        $label = $dryRun ? 'Dry run' : 'Done';
        $this->info("{$label}: {$this->created} created, {$this->skipped} skipped, {$this->failed} failed.");

        return self::SUCCESS;
    }

    private function parse(string $brandName, string $rawName): array
    {
        $name = $rawName;

        // Strip brand prefix (case-insensitive)
        $name = preg_replace('/^' . preg_quote($brandName, '/') . '\s*/i', '', $name);

        // Strip common product prefixes
        $name = preg_replace('/^(Futerał|Etui silikonowe|Etui)\s+/i', '', $name);

        // Strip brand again if repeated after prefix removal
        $name = preg_replace('/^' . preg_quote($brandName, '/') . '\s*/i', '', $name);

        // Strip trailing EAN (13+ digits)
        $name = preg_replace('/\s+\d{13,}$/', '', $name);

        // Strip "ochrona aparatu"
        $name = preg_replace('/\s+ochrona aparatu$/i', '', $name);

        $name = trim($name);

        // Try to extract device from name
        $devices = [];

        // Special case: "Iphone" without "Apple" prefix (e.g. "Magsafe Iphone 14")
        if (preg_match('/\b[Ii][Pp]hone\s+(.+)$/i', $name, $m)) {
            $pos = stripos($name, $m[0]);
            $productName = trim(substr($name, 0, $pos));
            $deviceModel = 'iPhone ' . trim($m[1]);

            if ($productName !== '') {
                $devices[] = ['brand' => 'Apple', 'model' => $deviceModel];
                $name = $productName;
            }
        }

        // Try known device brands
        if (empty($devices)) {
            foreach ($this->deviceBrands as $db) {
                $pattern = '/\b' . preg_quote($db, '/') . '\s+(.+)$/i';
                if (preg_match($pattern, $name, $m)) {
                    $productName = trim(substr($name, 0, strpos($name, $m[0])));

                    // Don't extract if it would leave the product name empty
                    // (means the whole name IS the device, like "Apple iPhone 7")
                    if ($productName === '') {
                        break;
                    }

                    $deviceModel = trim($m[1]);
                    $devices[] = ['brand' => $db, 'model' => $deviceModel];
                    $name = $productName;
                    break;
                }
            }
        }

        // Clean double spaces
        $name = preg_replace('/\s{2,}/', ' ', trim($name));

        return [
            'name' => $name,
            'devices' => $devices,
        ];
    }

    private function formatDevices(array $devices): string
    {
        if (empty($devices)) {
            return '<fg=gray>(universal)</>';
        }

        return implode(', ', array_map(
            fn ($d) => "<fg=cyan>{$d['brand']} {$d['model']}</>",
            $devices
        ));
    }

    private function resolveDeviceIds(array $devices): array
    {
        $ids = [];
        foreach ($devices as $d) {
            $brandName = $d['brand'];

            // Normalize brand name for lookup
            $brand = Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($brandName)])->first();

            if (!$brand) {
                $brand = Brand::create(['name' => $brandName]);
            }

            $device = Device::firstOrCreate(
                ['brand_id' => $brand->id, 'model' => $d['model']],
                ['created_by' => null]
            );

            $ids[] = $device->id;
        }
        return $ids;
    }
}