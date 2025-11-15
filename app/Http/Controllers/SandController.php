<?php

namespace App\Http\Controllers;

use App\Models\Sand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SandController extends Controller
{
    public function index(Request $request)
    {
        $query = Sand::query();

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sand_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('short_address', 'like', "%{$search}%");
            });
        }

        $sands = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('sands.index', compact('sands'));
    }

    public function create()
    {
        $units = \App\Models\Unit::orderBy('code')->get();

        return view('sands.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
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
        ]);

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('sands', $filename, 'public');
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

        // Auto-generate sand_name jika kosong
        if (empty($data['sand_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
            ]);
            $data['sand_name'] = implode(' ', $parts) ?: 'Pasir';
        }

        // Buat sand
        $sand = Sand::create($data);

        // Kalkulasi berat bersih dari berat kotor dan berat kemasan
        if ($sand->package_weight_gross && $sand->package_unit) {
            $sand->calculateNetWeight();
        }

        // Kalkulasi volume dari dimensi
        if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
            $sand->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
            $sand->calculateComparisonPrice();
        }

        $sand->save();

        return redirect()->route('sands.index')
            ->with('success', 'Data Pasir berhasil ditambahkan!');
    }

    public function show(Sand $sand)
    {
        return view('sands.show', compact('sand'));
    }

    public function edit(Sand $sand)
    {
        $units = \App\Models\Unit::orderBy('code')->get();

        return view('sands.edit', compact('sand', 'units'));
    }

    public function update(Request $request, Sand $sand)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
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
        ]);

        $data = $request->all();

        // Auto-generate sand_name jika kosong
        if (empty($data['sand_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
            ]);
            $data['sand_name'] = implode(' ', $parts) ?: 'Pasir';
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($sand->photo) {
                    Storage::disk('public')->delete($sand->photo);
                }

                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('sands', $filename, 'public');
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

        // Update sand
        $sand->update($data);

        // Kalkulasi berat bersih dari berat kotor dan berat kemasan
        if ($sand->package_weight_gross && $sand->package_unit) {
            $sand->calculateNetWeight();
        }

        // Kalkulasi volume dari dimensi
        if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
            $sand->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
            $sand->calculateComparisonPrice();
        } else {
            $sand->comparison_price_per_m3 = null;
        }

        $sand->save();

        return redirect()->route('sands.index')
            ->with('success', 'Data Pasir berhasil diupdate!');
    }

    public function destroy(Sand $sand)
    {
        // Hapus foto
        if ($sand->photo) {
            Storage::disk('public')->delete($sand->photo);
        }

        $sand->delete();

        return redirect()->route('sands.index')
            ->with('success', 'Data Pasir berhasil dihapus!');
    }

    /**
     * API untuk mendapatkan unique values per field
     */
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'type', 'brand', 'store', 'short_address', 'address',
        ];

        if (! in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Sand::query()
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
