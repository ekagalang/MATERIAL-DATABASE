<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query();

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('color_name', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%");
            });
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('materials.index', compact('materials'));
    }

    public function create()
    {
        $units = Unit::orderBy('code')->get();

        return view('materials.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:100',
            'color_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'volume_unit' => 'nullable|string|max:20',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time().'_'.$photo->getClientOriginalName();
                // Simpan ke disk 'public' agar dapat diakses via /storage
                $path = $photo->storeAs('materials', $filename, 'public');
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

        // Buat material
        $material = Material::create($data);

        // Jika berat bersih belum diisi, hitung dari (berat kotor - berat kemasan unit)
        if ((! $material->package_weight_net || $material->package_weight_net <= 0)
            && $material->package_weight_gross && $material->package_unit) {
            $material->calculateNetWeight();
        }
        // Kalkulasi harga komparasi per kg
        if ($material->purchase_price && $material->package_weight_net && $material->package_weight_net > 0) {
            $material->comparison_price_per_kg = $material->purchase_price / $material->package_weight_net;
        }

        $material->save();

        return redirect()->route('materials.index')
            ->with('success', 'Material berhasil ditambahkan!');
    }

    public function show(Material $material)
    {
        return view('materials.show', compact('material'));
    }

    public function edit(Material $material)
    {
        $units = Unit::orderBy('code')->get();

        return view('materials.edit', compact('material', 'units'));
    }

    public function update(Request $request, Material $material)
    {
        $request->validate([
            'material_name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:100',
            'color_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'volume_unit' => 'nullable|string|max:20',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($material->photo) {
                    Storage::disk('public')->delete($material->photo);
                }

                $filename = time().'_'.$photo->getClientOriginalName();
                // Simpan ke disk 'public' agar dapat diakses via /storage
                $path = $photo->storeAs('materials', $filename, 'public');
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

        // Update material
        $material->update($data);

        // Jika berat bersih belum diisi, hitung dari (berat kotor - berat kemasan unit)
        if ((! $material->package_weight_net || $material->package_weight_net <= 0)
            && $material->package_weight_gross && $material->package_unit) {
            $material->calculateNetWeight();
        }
        // Kalkulasi harga komparasi per kg
        if ($material->purchase_price && $material->package_weight_net && $material->package_weight_net > 0) {
            $material->comparison_price_per_kg = $material->purchase_price / $material->package_weight_net;
        } else {
            $material->comparison_price_per_kg = null;
        }

        $material->save();

        return redirect()->route('materials.index')
            ->with('success', 'Material berhasil diupdate!');
    }

    public function destroy(Material $material)
    {
        // Hapus foto
        if ($material->photo) {
            Storage::disk('public')->delete($material->photo);
        }

        $material->delete();

        return redirect()->route('materials.index')
            ->with('success', 'Material berhasil dihapus!');
    }

    // API untuk mendapatkan unique values per field
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'material_name', 'type', 'brand', 'sub_brand', 'color_code', 'color_name',
            'form', 'volume_unit', 'store', 'short_address', 'address', 'price_unit',
        ];

        if (! in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Material::query()
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
