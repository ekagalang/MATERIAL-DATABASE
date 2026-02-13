<?php

namespace App\Console\Commands;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetAndSeedStoreMaterialsCommand extends Command
{
    protected $signature = 'materials:reset-store-dataset {--force : Skip confirmation prompt}';

    protected $description = 'Reset material/store data and seed 10 stores with mixed complete/incomplete material coverage.';

    private const ALL_TYPES = ['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'];

    public function handle(): int
    {
        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                'Command ini akan menghapus data material, store, store location, availability, dan data kalkulasi terkait. Lanjutkan?',
                false,
            );

            if (!$confirmed) {
                $this->warn('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        try {
            $this->resetTables();

            DB::beginTransaction();
            $stores = $this->seedDataset();
            DB::commit();

            $this->info('Selesai reset dan isi ulang dataset material.');
            $this->renderSummary($stores);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('Gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function resetTables(): void
    {
        $tables = [
            'store_material_availabilities',
            'recommended_combinations',
            'brick_calculations',
            'material_calculations',
            'nats',
            'ceramics',
            'cats',
            'sands',
            'cements',
            'bricks',
            'materials',
            'store_locations',
            'stores',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * @return array<int, array{store: Store, location: StoreLocation}>
     */
    private function seedDataset(): array
    {
        mt_srand(20260212);

        $storeBlueprints = $this->storeBlueprints();
        $created = [];

        foreach ($storeBlueprints as $index => $blueprint) {
            $store = Store::create(['name' => $blueprint['name']]);
            $location = StoreLocation::create([
                'store_id' => $store->id,
                'address' => $blueprint['address'],
                'city' => $blueprint['city'],
                'province' => $blueprint['province'],
                'service_radius_km' => 10 + ($index % 6),
                'contact_name' => 'Admin ' . ($index + 1),
                'contact_phone' => '08123' . str_pad((string) (55000 + $index), 5, '0', STR_PAD_LEFT),
            ]);

            $availableTypes = $blueprint['available_types'];
            foreach ($availableTypes as $type) {
                $count = in_array($type, self::ALL_TYPES, true) ? ($this->isCompleteStore($availableTypes) ? 3 : 2) : 0;
                $this->createMaterials($type, $location, $count, $index + 1);
            }

            $created[] = ['store' => $store, 'location' => $location];
        }

        return $created;
    }

    /**
     * @param array<int, string> $availableTypes
     */
    private function isCompleteStore(array $availableTypes): bool
    {
        sort($availableTypes);
        $full = self::ALL_TYPES;
        sort($full);
        return $availableTypes === $full;
    }

    private function createMaterials(string $type, StoreLocation $location, int $count, int $seed): void
    {
        for ($i = 1; $i <= $count; $i++) {
            match ($type) {
                'brick' => $this->createBrick($location, $seed, $i),
                'cement' => $this->createCement($location, $seed, $i),
                'sand' => $this->createSand($location, $seed, $i),
                'cat' => $this->createCat($location, $seed, $i),
                'ceramic' => $this->createCeramic($location, $seed, $i),
                'nat' => $this->createNat($location, $seed, $i),
                default => null,
            };
        }
    }

    private function createBrick(StoreLocation $location, int $seed, int $i): void
    {
        $length = [18, 19, 20, 21][($seed + $i) % 4];
        $width = [8, 9, 10][($seed + $i) % 3];
        $height = [4, 5, 6][($seed + $i) % 3];
        $volume = ($length * $width * $height) / 1000000;
        $price = 650 + (($seed * 70 + $i * 55) % 900);

        $material = Brick::create([
            'material_name' => 'Bata',
            'type' => ['Merah', 'Press', 'Ringan'][($seed + $i) % 3],
            'brand' => ['Nusa', 'Kokoh', 'Prima', 'Mega'][($seed + $i) % 4] . ' Brick',
            'form' => ['Solid', 'Berlubang'][($seed + $i) % 2],
            'dimension_length' => $length,
            'dimension_width' => $width,
            'dimension_height' => $height,
            'package_volume' => $volume,
            'price_per_piece' => $price,
            'comparison_price_per_m3' => $volume > 0 ? $price / $volume : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function createCement(StoreLocation $location, int $seed, int $i): void
    {
        $weight = [40, 50][($seed + $i) % 2];
        $price = 50000 + (($seed * 1800 + $i * 1300) % 18000);

        $material = Cement::create([
            'cement_name' => 'Semen',
            'type' => ['PCC', 'PPC'][($seed + $i) % 2],
            'brand' => ['Tiga Roda', 'Gresik', 'Conch', 'Holcim'][($seed + $i) % 4],
            'sub_brand' => ['Konstruksi', 'Serbaguna', 'Premium'][($seed + $i) % 3],
            'code' => 'CEM-' . $seed . $i,
            'color' => 'Abu-abu',
            'package_unit' => 'Sak',
            'package_weight_gross' => $weight + 0.5,
            'package_weight_net' => $weight,
            'package_price' => $price,
            'comparison_price_per_kg' => $weight > 0 ? $price / $weight : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function createSand(StoreLocation $location, int $seed, int $i): void
    {
        $l = [1.0, 1.2, 1.5][($seed + $i) % 3];
        $w = [0.9, 1.0, 1.1][($seed + $i + 1) % 3];
        $h = [0.8, 0.9, 1.0][($seed + $i + 2) % 3];
        $volume = $l * $w * $h;
        $price = 230000 + (($seed * 25000 + $i * 17000) % 160000);

        $material = Sand::create([
            'sand_name' => 'Pasir',
            'type' => ['Pasang', 'Cor', 'Beton'][($seed + $i) % 3],
            'brand' => ['Lumajang', 'Bangka', 'Cileungsi'][($seed + $i) % 3],
            'package_unit' => 'Truk',
            'dimension_length' => $l,
            'dimension_width' => $w,
            'dimension_height' => $h,
            'package_volume' => $volume,
            'package_price' => $price,
            'comparison_price_per_m3' => $volume > 0 ? $price / $volume : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function createCat(StoreLocation $location, int $seed, int $i): void
    {
        $netWeight = [4.5, 5.0, 20.0][($seed + $i) % 3];
        $purchase = 110000 + (($seed * 14000 + $i * 9000) % 250000);

        $material = Cat::create([
            'cat_name' => 'Cat Tembok',
            'type' => ['Interior', 'Exterior'][($seed + $i) % 2],
            'brand' => ['Dulux', 'Nippon', 'Avian', 'Mowilex'][($seed + $i) % 4],
            'sub_brand' => ['Basic', 'Premium', 'Anti Jamur'][($seed + $i) % 3],
            'color_code' => 'CLR-' . $seed . $i,
            'color_name' => ['Putih', 'Abu', 'Cream', 'Biru'][($seed + $i) % 4],
            'package_unit' => ['Pail', 'Galon'][($seed + $i) % 2],
            'package_weight_gross' => $netWeight + 0.3,
            'package_weight_net' => $netWeight,
            'volume' => [4.0, 5.0, 20.0][($seed + $i) % 3],
            'purchase_price' => $purchase,
            'comparison_price_per_kg' => $netWeight > 0 ? $purchase / $netWeight : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function createCeramic(StoreLocation $location, int $seed, int $i): void
    {
        $length = [30, 40, 60][($seed + $i) % 3];
        $width = [30, 40, 60][($seed + $i + 1) % 3];
        $pieces = [4, 6, 8, 10][($seed + $i) % 4];
        $coverage = (($length * $width) / 10000) * $pieces;
        $price = 70000 + (($seed * 21000 + $i * 11000) % 160000);

        $material = Ceramic::create([
            'material_name' => 'Keramik',
            'type' => ['Lantai', 'Dinding'][($seed + $i) % 2],
            'brand' => ['Roman', 'Mulia', 'Platinum', 'Arwana'][($seed + $i) % 4],
            'sub_brand' => ['Standard', 'Signature', 'Prime'][($seed + $i) % 3],
            'code' => 'CRC-' . $seed . $i,
            'color' => ['White', 'Grey', 'Beige'][($seed + $i) % 3],
            'form' => 'Persegi',
            'surface' => ['Glossy', 'Matt'][($seed + $i) % 2],
            'dimension_length' => $length,
            'dimension_width' => $width,
            'dimension_thickness' => [0.7, 0.8, 0.9][($seed + $i) % 3],
            'packaging' => 'Dus',
            'pieces_per_package' => $pieces,
            'coverage_per_package' => $coverage,
            'price_per_package' => $price,
            'comparison_price_per_m2' => $coverage > 0 ? $price / $coverage : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function createNat(StoreLocation $location, int $seed, int $i): void
    {
        $netWeight = [1.0, 2.0, 5.0][($seed + $i) % 3];
        $price = 18000 + (($seed * 2500 + $i * 1300) % 18000);

        $material = Nat::create([
            'nat_name' => 'Nat Keramik',
            'type' => ['Regular', 'Non Sanded', 'Epoxy'][($seed + $i) % 3],
            'brand' => ['MU', 'AM', 'Sika', 'Lemkra'][($seed + $i) % 4],
            'sub_brand' => ['Basic', 'Premium'][($seed + $i) % 2],
            'code' => 'NAT-' . $seed . $i,
            'color' => ['White', 'Grey', 'Black'][($seed + $i) % 3],
            'package_unit' => 'Kg',
            'package_weight_gross' => $netWeight + 0.1,
            'package_weight_net' => $netWeight,
            'package_volume' => $netWeight / 1440,
            'package_price' => $price,
            'comparison_price_per_kg' => $netWeight > 0 ? $price / $netWeight : null,
            'store_location_id' => $location->id,
        ]);

        $this->syncAvailability($material, $location);
    }

    private function syncAvailability(object $material, StoreLocation $location): void
    {
        if (!method_exists($material, 'storeLocations')) {
            return;
        }

        $material->storeLocations()->syncWithoutDetaching([$location->id]);
    }

    /**
     * @return array<int, array{name: string, address: string, city: string, province: string, available_types: array<int, string>}>
     */
    private function storeBlueprints(): array
    {
        return [
            [
                'name' => 'TB Nusantara Jaya',
                'address' => 'Jl. Merdeka No. 1',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'available_types' => self::ALL_TYPES,
            ],
            [
                'name' => 'TB Bangun Sentosa',
                'address' => 'Jl. Cendana No. 12',
                'city' => 'Depok',
                'province' => 'Jawa Barat',
                'available_types' => self::ALL_TYPES,
            ],
            [
                'name' => 'Depot Material Prima',
                'address' => 'Jl. Raya Bogor No. 88',
                'city' => 'Bogor',
                'province' => 'Jawa Barat',
                'available_types' => self::ALL_TYPES,
            ],
            [
                'name' => 'Toko Mandiri Konstruksi',
                'address' => 'Jl. Veteran No. 20',
                'city' => 'Tangerang',
                'province' => 'Banten',
                'available_types' => self::ALL_TYPES,
            ],
            [
                'name' => 'TB Cipta Karya',
                'address' => 'Jl. Ki Hajar Dewantara No. 15',
                'city' => 'Bekasi',
                'province' => 'Jawa Barat',
                'available_types' => ['brick', 'cement', 'sand', 'cat', 'ceramic'],
            ],
            [
                'name' => 'Gudang Material Pertiwi',
                'address' => 'Jl. Jendral Sudirman No. 45',
                'city' => 'Serang',
                'province' => 'Banten',
                'available_types' => ['brick', 'cement', 'sand', 'cat', 'nat'],
            ],
            [
                'name' => 'TB Karya Utama',
                'address' => 'Jl. Diponegoro No. 30',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'available_types' => ['brick', 'sand', 'ceramic', 'nat'],
            ],
            [
                'name' => 'Material Hub Sejahtera',
                'address' => 'Jl. Melati No. 6',
                'city' => 'Cikarang',
                'province' => 'Jawa Barat',
                'available_types' => ['brick', 'cement', 'sand'],
            ],
            [
                'name' => 'TB Artha Bangun',
                'address' => 'Jl. Mawar No. 11',
                'city' => 'Tangerang Selatan',
                'province' => 'Banten',
                'available_types' => ['cat', 'ceramic', 'nat'],
            ],
            [
                'name' => 'Pusat Bahan Bangunan Raya',
                'address' => 'Jl. Pahlawan No. 9',
                'city' => 'Jakarta Timur',
                'province' => 'DKI Jakarta',
                'available_types' => ['brick', 'cement', 'cat'],
            ],
        ];
    }

    /**
     * @param array<int, array{store: Store, location: StoreLocation}> $stores
     */
    private function renderSummary(array $stores): void
    {
        $rows = [];
        foreach ($stores as $entry) {
            $locationId = $entry['location']->id;
            $counts = [
                'brick' => Brick::where('store_location_id', $locationId)->count(),
                'cement' => Cement::where('store_location_id', $locationId)->count(),
                'sand' => Sand::where('store_location_id', $locationId)->count(),
                'cat' => Cat::where('store_location_id', $locationId)->count(),
                'ceramic' => Ceramic::where('store_location_id', $locationId)->count(),
                'nat' => Nat::where('store_location_id', $locationId)->count(),
            ];

            $missing = collect($counts)
                ->filter(fn($count) => $count === 0)
                ->keys()
                ->implode(', ');

            $rows[] = [
                $entry['store']->name,
                $counts['brick'],
                $counts['cement'],
                $counts['sand'],
                $counts['cat'],
                $counts['ceramic'],
                $counts['nat'],
                $missing !== '' ? $missing : '-',
            ];
        }

        $this->table(
            ['Store', 'Brick', 'Cement', 'Sand', 'Cat', 'Ceramic', 'Nat', 'Missing'],
            $rows,
        );
    }
}
