<?php

namespace App\Http\Controllers;

use App\Models\Cement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CementController extends Controller
{
    public function index(Request $request)
    {
        $query = Cement::query()->with('packageUnit');

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cement_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('sub_brand', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = [
            'cement_name', 'type', 'brand', 'sub_brand', 'code', 'color',
            'package_unit', 'package_weight_gross', 'package_weight_net',
            'store', 'short_address', 'package_price', 'comparison_price_per_kg', 'created_at',
        ];

        // Default sorting jika tidak ada atau tidak valid
        if (! $sortBy || ! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
            $sortDirection = 'desc';
        } else {
            // Validasi direction
            if (! in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
        }

        $cements = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('cements.index', compact('cements'));
    }

    public function create()
    {
        $units = Cement::getAvailableUnits();

        return view('cements.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('cements', $filename, 'public');
                if ($path) {
                    $data['photo'] = $path;
                    \Log::info('Photo uploaded successfully: '.$path);
                } else {
                    \Log::error('Failed to store photo');
                }
            } else {
                \Log::error('Invalid photo file: '.$photo->getErrorMessage());
            }
        }

        // Auto-generate cement_name jika kosong
        if (empty($data['cement_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['cement_name'] = implode(' ', $parts) ?: 'Semen';
        }

        // Buat cement
        $cement = Cement::create($data);

        // Kalkulasi volume dari dimensi
        if ($cement->dimension_length && $cement->dimension_width && $cement->dimension_height) {
            $cement->calculateVolume();
        }

        // Kalkulasi berat bersih jika belum diisi
        if ((! $cement->package_weight_net || $cement->package_weight_net <= 0)
            && $cement->package_weight_gross && $cement->package_unit) {
            $cement->calculateNetWeight();
        }

        // Kalkulasi harga komparasi per kg
        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->comparison_price_per_kg = $cement->package_price / $cement->package_weight_net;
        }

        $cement->save();

        return redirect()->route('cements.index')
            ->with('success', 'Semen berhasil ditambahkan!');
    }

    public function show(Cement $cement)
    {
        $cement->load('packageUnit');

        return view('cements.show', compact('cement'));
    }

    public function edit(Cement $cement)
    {
        $units = Cement::getAvailableUnits();

        return view('cements.edit', compact('cement', 'units'));
    }

    public function update(Request $request, Cement $cement)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Auto-generate cement_name jika kosong
        if (empty($data['cement_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['cement_name'] = implode(' ', $parts) ?: 'Semen';
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($cement->photo) {
                    Storage::disk('public')->delete($cement->photo);
                }

                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('cements', $filename, 'public');
                if ($path) {
                    $data['photo'] = $path;
                    \Log::info('Photo updated successfully: '.$path);
                } else {
                    \Log::error('Failed to update photo');
                }
            } else {
                \Log::error('Invalid photo file on update: '.$photo->getErrorMessage());
            }
        }

        // Update cement
        $cement->update($data);

        // Kalkulasi volume dari dimensi
        if ($cement->dimension_length && $cement->dimension_width && $cement->dimension_height) {
            $cement->calculateVolume();
        }

        // Kalkulasi berat bersih jika belum diisi
        if ((! $cement->package_weight_net || $cement->package_weight_net <= 0)
            && $cement->package_weight_gross && $cement->package_unit) {
            $cement->calculateNetWeight();
        }

        // Kalkulasi harga komparasi per kg
        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->comparison_price_per_kg = $cement->package_price / $cement->package_weight_net;
        } else {
            $cement->comparison_price_per_kg = null;
        }

        $cement->save();

        return redirect()->route('cements.index')
            ->with('success', 'Semen berhasil diupdate!');
    }

    public function destroy(Cement $cement)
    {
        // Hapus foto
        if ($cement->photo) {
            Storage::disk('public')->delete($cement->photo);
        }

        $cement->delete();

        return redirect()->route('cements.index')
            ->with('success', 'Semen berhasil dihapus!');
    }

    // API untuk mendapatkan unique values per field
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'cement_name', 'type', 'brand', 'sub_brand', 'code', 'color',
            'store', 'short_address', 'address', 'price_unit',
        ];

        if (! in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Cement::query()
            ->whereNotNull($field)
            ->where($field, '!=', '');

        if ($search !== '') {
            $query->where($field, 'like', "%{$search}%");
        }

        // Ambil nilai unik, dibatasi
        $values = $query
            ->select($field)
            ->groupBy($field)
            ->orderBy($field)
            ->limit($limit)
            ->pluck($field);

        return response()->json($values);
    }
}
