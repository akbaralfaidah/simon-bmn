<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories from data
        $categories = [
            'Alat Penghancur Kertas' => AssetCategory::firstOrCreate(['name' => 'Alat Penghancur Kertas'], ['code' => 'APK']),
            'Laptop' => AssetCategory::firstOrCreate(['name' => 'Laptop'], ['code' => 'LPT']),
            'Printer' => AssetCategory::firstOrCreate(['name' => 'Printer'], ['code' => 'PRT']),
            'Kendaraan Dinas' => AssetCategory::firstOrCreate(['name' => 'Kendaraan Dinas'], ['code' => 'KDN']),
            'Peralatan Kantor' => AssetCategory::firstOrCreate(['name' => 'Peralatan Kantor'], ['code' => 'PKT']),
            'Peralatan IT' => AssetCategory::firstOrCreate(['name' => 'Peralatan IT'], ['code' => 'PIT']),
            'Perabotan Kantor' => AssetCategory::firstOrCreate(['name' => 'Perabotan Kantor'], ['code' => 'PBK']),
            'Lain-lain' => AssetCategory::firstOrCreate(['name' => 'Lain-lain'], ['code' => 'LLN']),
        ];

        // Import Sumatera data
        $sumatera = json_decode(file_get_contents(database_path('data/bmn_sumatera.json')), true);
        foreach ($sumatera as $item) {
            $catName = $this->detectCategory($item['name']);
            $cat = $categories[$catName] ?? $categories['Lain-lain'];

            Asset::create([
                'id' => Str::uuid(),
                'name' => $item['name'],
                'category_id' => $cat->id,
                'item_code' => $item['item_code'],
                'nup' => $item['nup'],
                'brand_type' => $item['brand_type'],
                'value' => $item['value'],
                'condition' => $item['condition'] ?: 'Baik',
                'status' => 'active',
                'acquisition_date' => $this->parseDate($item['purchase_date']),
            ]);
        }

        // Import Jambi inventaris
        $jambi = json_decode(file_get_contents(database_path('data/bmn_jambi.json')), true);
        foreach ($jambi as $item) {
            $catName = $this->detectCategory($item['name']);
            $cat = $categories[$catName] ?? $categories['Lain-lain'];

            Asset::create([
                'id' => Str::uuid(),
                'name' => $item['name'],
                'category_id' => $cat->id,
                'condition' => $item['condition'] ?: 'Baik',
                'status' => 'active',
            ]);
        }

        // Import Laptop holders
        $laptops = json_decode(file_get_contents(database_path('data/bmn_laptops.json')), true);
        $laptopCat = $categories['Laptop'];
        foreach ($laptops as $item) {
            $laptopName = $item['laptop_new'] ?: $item['laptop_old'];
            $nup = $item['nup_new'] ?: $item['nup_old'];
            if (! $laptopName || $laptopName === 'None') {
                continue;
            }

            Asset::create([
                'id' => Str::uuid(),
                'name' => 'Laptop '.trim($laptopName),
                'category_id' => $laptopCat->id,
                'nup' => $nup !== 'None' ? $nup : null,
                'brand_type' => trim($laptopName),
                'condition' => 'Baik',
                'status' => 'active',
            ]);
        }

        $this->command->info('Imported '.Asset::count().' assets total.');
    }

    private function detectCategory(string $name): string
    {
        $name = strtolower($name);
        if (str_contains($name, 'laptop') || str_contains($name, 'notebook')) {
            return 'Laptop';
        }
        if (str_contains($name, 'printer') || str_contains($name, 'epson') || str_contains($name, 'canon')) {
            return 'Printer';
        }
        if (str_contains($name, 'penghancur')) {
            return 'Alat Penghancur Kertas';
        }
        if (str_contains($name, 'kendaraan') || str_contains($name, 'mobil') || str_contains($name, 'motor')) {
            return 'Kendaraan Dinas';
        }
        if (str_contains($name, 'meja') || str_contains($name, 'kursi') || str_contains($name, 'lemari') || str_contains($name, 'rak')) {
            return 'Perabotan Kantor';
        }
        if (str_contains($name, 'server') || str_contains($name, 'switch') || str_contains($name, 'router') || str_contains($name, 'ups') || str_contains($name, 'monitor')) {
            return 'Peralatan IT';
        }

        return 'Peralatan Kantor';
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date || $date === 'None') {
            return null;
        }
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
