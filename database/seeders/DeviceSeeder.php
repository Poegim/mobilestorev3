<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Seed the most popular devices.
     *
     * Models are stored WITHOUT the brand name, because Device::full_name
     * prepends it ("Apple" + "iPhone 16" => "Apple iPhone 16").
     *
     * Brands are matched with where('name', ...) and are NOT created here:
     * a missing brand is skipped with a warning. The seeder is idempotent
     * (upsert on the unique brand_id + model pair).
     */
    public function run(): void
    {
        foreach ($this->catalog() as $brandName => $models) {
            $brand = Brand::where('name', $brandName)->first();

            if (! $brand) {
                $this->command?->warn("Brand \"{$brandName}\" not found - skipping " . count($models) . ' models.');
                continue;
            }

            $now = now();

            $rows = collect($models)
                ->unique()
                ->map(fn (string $model) => [
                    'brand_id'   => $brand->id,
                    'model'      => $model,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                Device::upsert($chunk, ['brand_id', 'model'], ['updated_at']);
            }

            $this->command?->info("{$brandName}: " . count($rows) . ' models');
        }
    }

    /**
     * @return array<string, list<string>> brand name => models
     */
    private function catalog(): array
    {
        return [
            // Apple - 2016 onwards
            'Apple' => [
                'iPhone SE (2016)',
                'iPhone 7', 'iPhone 7 Plus',
                'iPhone 8', 'iPhone 8 Plus',
                'iPhone X', 'iPhone XR', 'iPhone XS', 'iPhone XS Max',
                'iPhone 11', 'iPhone 11 Pro', 'iPhone 11 Pro Max',
                'iPhone SE (2020)',
                'iPhone 12 mini', 'iPhone 12', 'iPhone 12 Pro', 'iPhone 12 Pro Max',
                'iPhone 13 mini', 'iPhone 13', 'iPhone 13 Pro', 'iPhone 13 Pro Max',
                'iPhone SE (2022)',
                'iPhone 14', 'iPhone 14 Plus', 'iPhone 14 Pro', 'iPhone 14 Pro Max',
                'iPhone 15', 'iPhone 15 Plus', 'iPhone 15 Pro', 'iPhone 15 Pro Max',
                'iPhone 16e', 'iPhone 16', 'iPhone 16 Plus', 'iPhone 16 Pro', 'iPhone 16 Pro Max',
                'iPhone 17', 'iPhone Air', 'iPhone 17 Pro', 'iPhone 17 Pro Max',
            ],

            // Samsung - 2016 onwards
            'Samsung' => [
                // S line
                'Galaxy S7', 'Galaxy S7 edge',
                'Galaxy S8', 'Galaxy S8+',
                'Galaxy S9', 'Galaxy S9+',
                'Galaxy S10e', 'Galaxy S10', 'Galaxy S10+',
                'Galaxy S20', 'Galaxy S20+', 'Galaxy S20 Ultra', 'Galaxy S20 FE',
                'Galaxy S21', 'Galaxy S21+', 'Galaxy S21 Ultra', 'Galaxy S21 FE',
                'Galaxy S22', 'Galaxy S22+', 'Galaxy S22 Ultra',
                'Galaxy S23', 'Galaxy S23+', 'Galaxy S23 Ultra', 'Galaxy S23 FE',
                'Galaxy S24', 'Galaxy S24+', 'Galaxy S24 Ultra', 'Galaxy S24 FE',
                'Galaxy S25', 'Galaxy S25+', 'Galaxy S25 Ultra', 'Galaxy S25 Edge', 'Galaxy S25 FE',
                'Galaxy S26', 'Galaxy S26+', 'Galaxy S26 Ultra',
                // Note line
                'Galaxy Note 7', 'Galaxy Note 8', 'Galaxy Note 9',
                'Galaxy Note 10', 'Galaxy Note 10+',
                'Galaxy Note 20', 'Galaxy Note 20 Ultra',
                // Foldables
                'Galaxy Z Flip', 'Galaxy Z Flip3', 'Galaxy Z Flip4', 'Galaxy Z Flip5',
                'Galaxy Z Flip6', 'Galaxy Z Flip7', 'Galaxy Z Flip7 FE',
                'Galaxy Z Fold', 'Galaxy Z Fold2', 'Galaxy Z Fold3', 'Galaxy Z Fold4',
                'Galaxy Z Fold5', 'Galaxy Z Fold6', 'Galaxy Z Fold7',
                // A line
                'Galaxy A3 (2017)', 'Galaxy A5 (2017)', 'Galaxy A7 (2017)',
                'Galaxy A6', 'Galaxy A6+', 'Galaxy A7 (2018)', 'Galaxy A8 (2018)',
                'Galaxy A10', 'Galaxy A20e', 'Galaxy A30', 'Galaxy A40', 'Galaxy A50', 'Galaxy A70',
                'Galaxy A12', 'Galaxy A13', 'Galaxy A14', 'Galaxy A15', 'Galaxy A16',
                'Galaxy A21s', 'Galaxy A22', 'Galaxy A23', 'Galaxy A25', 'Galaxy A26',
                'Galaxy A32', 'Galaxy A33', 'Galaxy A34', 'Galaxy A35', 'Galaxy A36',
                'Galaxy A41', 'Galaxy A42', 'Galaxy A51', 'Galaxy A52', 'Galaxy A52s',
                'Galaxy A53', 'Galaxy A54', 'Galaxy A55', 'Galaxy A56',
                'Galaxy A71', 'Galaxy A72', 'Galaxy A73',
                // M line (most popular only)
                'Galaxy M12', 'Galaxy M13', 'Galaxy M23', 'Galaxy M33', 'Galaxy M52',
            ],

            // Motorola - 2021 onwards
            'Motorola' => [
                // Moto G
                'Moto G10', 'Moto G20', 'Moto G30', 'Moto G50', 'Moto G60', 'Moto G60s', 'Moto G100',
                'Moto G22', 'Moto G31', 'Moto G32', 'Moto G41', 'Moto G42', 'Moto G51', 'Moto G52',
                'Moto G62', 'Moto G71', 'Moto G72', 'Moto G82', 'Moto G200',
                'Moto G13', 'Moto G23', 'Moto G53', 'Moto G73', 'Moto G84',
                'Moto G04', 'Moto G14', 'Moto G24', 'Moto G34', 'Moto G54', 'Moto G64', 'Moto G85',
                'Moto G05', 'Moto G15', 'Moto G35', 'Moto G55', 'Moto G75', 'Moto G86',
                // Moto E
                'Moto E13', 'Moto E14', 'Moto E22', 'Moto E32', 'Moto E40',
                // Edge
                'Edge 20', 'Edge 20 Lite', 'Edge 20 Pro',
                'Edge 30', 'Edge 30 Fusion', 'Edge 30 Neo', 'Edge 30 Pro', 'Edge 30 Ultra',
                'Edge 40', 'Edge 40 Neo', 'Edge 40 Pro',
                'Edge 50 Fusion', 'Edge 50 Neo', 'Edge 50 Pro', 'Edge 50 Ultra',
                'Edge 60', 'Edge 60 Fusion', 'Edge 60 Pro', 'Edge 60 Stylus',
                // Razr
                'Razr 40', 'Razr 40 Ultra', 'Razr 50', 'Razr 50 Ultra', 'Razr 60', 'Razr 60 Ultra',
            ],

            // Xiaomi (incl. Redmi and Poco sub-lines) - 2021 onwards
            'Xiaomi' => [
                // Xiaomi flagships
                'Mi 11', 'Mi 11 Ultra', 'Mi 11 Lite', 'Mi 11 Lite 5G NE', 'Mi 11i',
                '11T', '11T Pro',
                '12', '12 Pro', '12X', '12 Lite', '12T', '12T Pro',
                '13', '13 Pro', '13 Lite', '13T', '13T Pro',
                '14', '14 Ultra', '14T', '14T Pro',
                '15', '15 Ultra', '15T', '15T Pro',
                // Redmi Note
                'Redmi Note 10', 'Redmi Note 10S', 'Redmi Note 10 Pro', 'Redmi Note 10 5G',
                'Redmi Note 11', 'Redmi Note 11S', 'Redmi Note 11 Pro', 'Redmi Note 11 Pro+ 5G',
                'Redmi Note 12', 'Redmi Note 12S', 'Redmi Note 12 5G', 'Redmi Note 12 Pro', 'Redmi Note 12 Pro+',
                'Redmi Note 13', 'Redmi Note 13 5G', 'Redmi Note 13 Pro', 'Redmi Note 13 Pro+',
                'Redmi Note 14', 'Redmi Note 14 5G', 'Redmi Note 14 Pro', 'Redmi Note 14 Pro+',
                'Redmi Note 15', 'Redmi Note 15 Pro', 'Redmi Note 15 Pro+',
                // Redmi (budget)
                'Redmi 10', 'Redmi 10C', 'Redmi 12', 'Redmi 12C', 'Redmi 13', 'Redmi 13C', 'Redmi 14C', 'Redmi A3',
                // Poco
                'Poco X3 Pro', 'Poco X4 Pro 5G', 'Poco X5', 'Poco X5 Pro', 'Poco X6', 'Poco X6 Pro',
                'Poco X7', 'Poco X7 Pro',
                'Poco F3', 'Poco F4', 'Poco F4 GT', 'Poco F5', 'Poco F5 Pro', 'Poco F6', 'Poco F6 Pro',
                'Poco F7', 'Poco F7 Pro', 'Poco F7 Ultra',
                'Poco M4 Pro', 'Poco M5', 'Poco M5s', 'Poco M6', 'Poco M6 Pro', 'Poco C65', 'Poco C75',
            ],

            // Realme - popular models
            'Realme' => [
                '8', '8 Pro', '8i',
                '9', '9 Pro', '9 Pro+', '9i',
                '10', '10 Pro', '10 Pro+',
                '11', '11 Pro', '11 Pro+',
                '12', '12+', '12 Pro', '12 Pro+',
                '13', '13+', '13 Pro', '13 Pro+',
                '14 Pro', '14 Pro+', '14x',
                'GT Neo 3', 'GT 2', 'GT 2 Pro', 'GT 3', 'GT 5', 'GT 6', 'GT 7', 'GT 7 Pro',
                'C21Y', 'C25', 'C35', 'C53', 'C55', 'C67', 'C75',
                'Narzo 50', 'Narzo 60', 'Narzo 70 Pro',
            ],

            // Oppo - popular models
            'Oppo' => [
                // Find
                'Find X3 Lite', 'Find X3 Neo', 'Find X3 Pro',
                'Find X5 Lite', 'Find X5', 'Find X5 Pro',
                'Find X6', 'Find X6 Pro',
                'Find X7', 'Find X7 Ultra',
                'Find X8', 'Find X8 Pro',
                'Find X9', 'Find X9 Pro',
                'Find N2 Flip', 'Find N3 Flip', 'Find N3',
                // Reno
                'Reno5', 'Reno6', 'Reno6 Pro',
                'Reno7', 'Reno7 Lite', 'Reno7 Pro',
                'Reno8', 'Reno8 Lite', 'Reno8 Pro', 'Reno8 T',
                'Reno10', 'Reno10 Pro', 'Reno10 Pro+',
                'Reno11', 'Reno11 F', 'Reno11 Pro',
                'Reno12', 'Reno12 F', 'Reno12 Pro',
                'Reno13', 'Reno13 F', 'Reno13 Pro',
                'Reno14', 'Reno14 F', 'Reno14 Pro',
                // A series
                'A16', 'A16s', 'A17', 'A18', 'A38', 'A53s', 'A54', 'A54s', 'A57', 'A58', 'A60',
                'A74', 'A78', 'A79', 'A80', 'A94', 'A96', 'A98',
            ],
        ];
    }
}