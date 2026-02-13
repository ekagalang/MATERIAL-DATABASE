<?php

// app/Http/Controllers/Api/StoreSearchController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreSearchController extends Controller
{
    /**
     * Search stores by name and address
     * GET /api/stores/search?q=TB.%20Abadi
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        // Parse input: "TB. Abadi - Jl. Merpati" → extract store name
        $storeName = $this->extractStoreName($query);

        // Search stores with their locations
        $stores = Store::with('locations')
            ->where('name', 'like', "%{$storeName}%")
            ->limit(10)
            ->get()
            ->map(function ($store) {
                return $store->locations->map(function ($location) use ($store) {
                    return [
                        'id' => $location->id,
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'address' => $location->address,
                        'full_address' => $location->full_address,
                        'display_text' => $store->name . ($location->address ? ' - ' . $location->address : ''),
                        'is_incomplete' => $location->is_incomplete,
                    ];
                });
            })
            ->flatten(1)
            ->filter(function ($item) use ($query) {
                // Filter by full text match
                return stripos($item['display_text'], $query) !== false;
            })
            ->values();

        return response()->json($stores);
    }

    /**
     * Get all store names for autocomplete
     * GET /api/stores/all-stores?search=TB&limit=20
     */
    public function allStores(Request $request)
    {
        $search = $request->input('search', '');
        $limit = (int) $request->input('limit', 20);

        $query = Store::query()->orderBy('name');

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $stores = $query->limit($limit)->pluck('name')->unique()->values();

        return response()->json($stores);
    }

    /**
     * Get addresses by store name for autocomplete
     * GET /api/stores/addresses-by-store?store=TB.%20Abadi&search=Jl&limit=20
     */
    public function addressesByStore(Request $request)
    {
        $storeName = $request->input('store', '');
        $search = $request->input('search', '');
        $limit = (int) $request->input('limit', 20);

        if (empty($storeName)) {
            return response()->json([]);
        }

        $store = Store::where('name', $storeName)->first();

        if (!$store) {
            return response()->json([]);
        }

        $query = $store->locations();

        if (!empty($search)) {
            $query->where('address', 'like', "%{$search}%");
        }

        $addresses = $query->limit($limit)->pluck('address')->filter()->unique()->values();

        return response()->json($addresses);
    }

    /**
     * Quick create store with location from parsed string
     * POST /api/stores/quick-create
     * Body: { "input": "TB. Abadi - Jl. Merpati" }
     *
     * Returns existing location if store+address already exists
     */
    public function quickCreate(Request $request)
    {
        $input = $request->input('input', '');

        if (empty($input)) {
            return response()->json(['error' => 'Input cannot be empty'], 422);
        }

        try {
            DB::beginTransaction();

            // Parse input
            $parsed = $this->parseStoreInput($input);
            $storeName = $parsed['store_name'];
            $address = $parsed['address'];

            if (empty($storeName)) {
                return response()->json(['error' => 'Store name cannot be empty'], 422);
            }

            // Find or create store (case-insensitive search)
            $store = Store::whereRaw('LOWER(name) = ?', [strtolower($storeName)])->first();

            if (!$store) {
                $store = Store::create(['name' => $storeName]);
            }

            // Find or create location (case-insensitive search for address)
            $location = $store
                ->locations()
                ->whereRaw('LOWER(COALESCE(address, \'\')) = ?', [strtolower($address)])
                ->first();

            if (!$location) {
                $location = $store->locations()->create([
                    'address' => $address ?: null,
                ]);
            }

            DB::commit();

            return response()->json([
                'id' => $location->id,
                'store_id' => $store->id,
                'store_name' => $store->name,
                'address' => $location->address,
                'full_address' => $location->full_address ?? $location->address,
                'display_text' => $store->name . ($location->address ? ' - ' . $location->address : ''),
                'is_new' => $location->wasRecentlyCreated,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Extract store name from input
     * "TB. Abadi - Jl. Merpati" → "TB. Abadi"
     */
    private function extractStoreName(string $input): string
    {
        if (strpos($input, '-') !== false) {
            return trim(explode('-', $input)[0]);
        }

        return trim($input);
    }

    /**
     * Helper: Parse full input into store name and address
     */
    private function parseStoreInput(string $input): array
    {
        $parts = explode('-', $input, 2);

        return [
            'store_name' => trim($parts[0] ?? ''),
            'address' => trim($parts[1] ?? ''),
        ];
    }
}
