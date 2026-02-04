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

class MigrateStoresToNewSystem extends Command
{
    protected $signature = 'stores:migrate
                            {--dry-run : Show what would be migrated without making changes}
                            {--link-only : Only link existing materials to store_location_id (skip store creation)}
                            {--force : Force re-link all materials even if already linked}';

    protected $description = 'Migrate existing store/address data from materials to Store/StoreLocation tables';

    protected $materialModels = [
        'bricks' => Brick::class,
        'cements' => Cement::class,
        'nats' => Nat::class,
        'sands' => Sand::class,
        'ceramics' => Ceramic::class,
        'cats' => Cat::class,
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $linkOnly = $this->option('link-only');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        if ($force) {
            $this->warn('⚡ FORCE MODE - Will re-link all materials');
        }

        if (!$linkOnly) {
            $this->info('📦 Step 1: Extracting unique stores and addresses from materials...');
            $this->extractAndCreateStores($isDryRun);
        }

        $this->info('🔗 Step 2: Linking materials to store_location_id...');
        $this->linkMaterialsToStoreLocations($isDryRun, $force);

        $this->newLine();
        $this->info('✅ Migration completed!');

        if ($isDryRun) {
            $this->warn('Run without --dry-run to apply changes.');
        }
    }

    protected function extractAndCreateStores(bool $isDryRun): void
    {
        $storeAddressPairs = collect();

        // Collect all unique store+address pairs from all material tables
        foreach ($this->materialModels as $tableName => $modelClass) {
            $this->info("  Scanning {$tableName}...");

            $pairs = DB::table($tableName)
                ->select('store', 'address')
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->distinct()
                ->get();

            foreach ($pairs as $pair) {
                $key = strtolower(trim($pair->store)) . '|' . strtolower(trim($pair->address ?? ''));
                $storeAddressPairs[$key] = [
                    'store' => trim($pair->store),
                    'address' => trim($pair->address ?? ''),
                ];
            }
        }

        $this->info("  Found {$storeAddressPairs->count()} unique store+address combinations");

        // Group by store name
        $storeGroups = $storeAddressPairs->groupBy(function ($item) {
            return strtolower($item['store']);
        });

        $this->info("  Found {$storeGroups->count()} unique stores");

        // Create stores and locations
        $storesCreated = 0;
        $locationsCreated = 0;

        foreach ($storeGroups as $storeKey => $locations) {
            $storeName = $locations->first()['store'];

            // Check if store already exists
            $store = Store::whereRaw('LOWER(name) = ?', [$storeKey])->first();

            if (!$store) {
                if (!$isDryRun) {
                    $store = Store::create(['name' => $storeName]);
                }
                $storesCreated++;
                $this->line("    + Store: {$storeName}");
            } else {
                $this->line("    = Store exists: {$storeName}");
            }

            // Create locations for this store
            foreach ($locations as $loc) {
                $address = $loc['address'];

                if ($store) {
                    // Handle NULL/empty address matching
                    $existingLocation = null;
                    if ($address === '') {
                        $existingLocation = StoreLocation::where('store_id', $store->id)
                            ->where(function ($q) {
                                $q->whereNull('address')
                                  ->orWhere('address', '');
                            })
                            ->first();
                    } else {
                        $existingLocation = StoreLocation::where('store_id', $store->id)
                            ->whereRaw('LOWER(address) = ?', [strtolower($address)])
                            ->first();
                    }

                    if (!$existingLocation) {
                        if (!$isDryRun) {
                            StoreLocation::create([
                                'store_id' => $store->id,
                                'address' => $address ?: null,
                            ]);
                        }
                        $locationsCreated++;
                        $this->line("      + Location: " . ($address ?: '(no address)'));
                    } else {
                        $this->line("      = Location exists: " . ($address ?: '(no address)'));
                    }
                } else {
                    // Dry run - just count
                    $locationsCreated++;
                    $this->line("      + Location: " . ($address ?: '(no address)'));
                }
            }
        }

        $this->newLine();
        $this->info("  Summary: {$storesCreated} stores, {$locationsCreated} locations to create");
    }

    protected function linkMaterialsToStoreLocations(bool $isDryRun, bool $force = false): void
    {
        $totalLinked = 0;
        $totalSkipped = 0;
        $totalAlreadyLinked = 0;

        foreach ($this->materialModels as $tableName => $modelClass) {
            $this->info("  Processing {$tableName}...");

            // Get ALL materials with store name (not just those without store_location_id)
            // This ensures we also attach to many-to-many for existing materials
            $materials = $modelClass
                ::whereNotNull('store')
                ->where('store', '!=', '')
                ->get();

            $linked = 0;
            $skipped = 0;
            $alreadyLinked = 0;

            foreach ($materials as $material) {
                $storeName = trim($material->store);
                $address = trim($material->address ?? '');

                // Find the store (case-insensitive)
                $store = Store::whereRaw('LOWER(name) = ?', [strtolower($storeName)])->first();

                if (!$store) {
                    $this->line("      ⚠ Store not found: {$storeName}");
                    $skipped++;
                    continue;
                }

                // Find the location - handle both NULL and empty string
                $location = null;

                if ($address === '') {
                    // Look for location with NULL or empty address
                    $location = StoreLocation::where('store_id', $store->id)
                        ->where(function ($q) {
                            $q->whereNull('address')
                              ->orWhere('address', '');
                        })
                        ->first();
                } else {
                    // Look for location with matching address (case-insensitive)
                    $location = StoreLocation::where('store_id', $store->id)
                        ->whereRaw('LOWER(address) = ?', [strtolower($address)])
                        ->first();
                }

                // Fallback: if no exact match, try to find any location for this store
                if (!$location) {
                    $location = StoreLocation::where('store_id', $store->id)->first();
                    if ($location) {
                        $this->line("      ⚠ Address mismatch for '{$storeName}', using first location");
                    }
                }

                if ($location) {
                    if (!$isDryRun) {
                        $needsUpdate = false;

                        // Update direct column if not set OR force mode
                        if (!$material->store_location_id || $force) {
                            $material->store_location_id = $location->id;
                            $material->save();
                            $needsUpdate = true;
                        }

                        // Attach to many-to-many if not already attached OR force mode
                        $alreadyAttached = $material->storeLocations()->where('store_location_id', $location->id)->exists();
                        if (!$alreadyAttached || $force) {
                            if ($force) {
                                // Force mode: sync to ensure only the correct location is attached
                                $material->storeLocations()->sync([$location->id]);
                            } else {
                                $material->storeLocations()->syncWithoutDetaching([$location->id]);
                            }
                            $needsUpdate = true;
                        }

                        if ($needsUpdate) {
                            $linked++;
                        } else {
                            $alreadyLinked++;
                        }
                    } else {
                        // Dry run - count what would be linked
                        $alreadyAttached = $material->storeLocations()->where('store_location_id', $location->id)->exists();
                        if (!$material->store_location_id || !$alreadyAttached || $force) {
                            $linked++;
                        } else {
                            $alreadyLinked++;
                        }
                    }
                } else {
                    $this->line("      ⚠ No location found for: {$storeName} - {$address}");
                    $skipped++;
                }
            }

            $this->line("    Linked: {$linked}, Already linked: {$alreadyLinked}, Skipped: {$skipped}");
            $totalLinked += $linked;
            $totalSkipped += $skipped;
            $totalAlreadyLinked += $alreadyLinked;
        }

        $this->newLine();
        $this->info("  Total: {$totalLinked} materials linked, {$totalAlreadyLinked} already linked, {$totalSkipped} skipped");
    }
}

/*
Command migrasi data

  File: app/Console/Commands/MigrateStoresToNewSystem.php

  Cara Menjalankan

  # 1. Jalankan migration untuk menambah column store_location_id
  php artisan migrate

  # 2. Preview data yang akan dimigrasi (tanpa mengubah data)
  php artisan stores:migrate --dry-run

  # 3. Jalankan migrasi data (buat Store + link material)
  php artisan stores:migrate

  # 4. Jika hanya ingin link ulang (store sudah ada)
  php artisan stores:migrate --link-only

  # 5. Force re-link semua material (termasuk yang sudah ter-link)
  php artisan stores:migrate --link-only --force

  Struktur Akhir
  ┌───────────────────┬────────────────────────────────────┐
  │  Tabel Material   │              Columns               │
  ├───────────────────┼────────────────────────────────────┤
  │ store             │ ✅ Tetap ada (text, untuk display) │
  ├───────────────────┼────────────────────────────────────┤
  │ address           │ ✅ Tetap ada (text, untuk display) │
  ├───────────────────┼────────────────────────────────────┤
  │ store_location_id │ ✅ Baru (FK ke store_locations)    │
  └───────────────────┴────────────────────────────────────┘

  Command ini akan:
  1. Membuat Store dan StoreLocation dari data material yang ada
  2. Menyimpan store_location_id ke kolom langsung di material
  3. Meng-attach material ke relasi many-to-many storeLocations()

  Keuntungan pendekatan hybrid:
  - Data lama tetap aman
  - Bisa gradual migration
  - Relasi ke Store system baru sudah siap
  - Nanti jika mau hapus columns store & address, tinggal buat migration baru
*/
