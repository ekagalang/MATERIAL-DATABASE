<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatController extends Controller
{
    public function index(Request $request)
    {
        $query = Cat::query();
        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cat_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('color_name', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%");
            });
        }

        $cats = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('cats.index', compact('cats'));
    }

    public function create()
    {
        $units = Unit::orderBy('code')->get();

        return view('cats.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cat_name' => 'nullable|string|max:255',
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
                $path = $photo->storeAs('cats', $filename, 'public');
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

        // Auto-generate cat_name jika kosong
        if (empty($data['cat_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['color_name'] ?? '',
                ($data['volume'] ?? '') . ($data['volume_unit'] ?? '')
            ]);
            $data['cat_name'] = implode(' ', $parts) ?: 'Cat';
        }

        // Buat cat
        $cat = Cat::create($data);

        // Jika berat bersih belum diisi, hitung dari (berat kotor - berat kemasan unit)
        if ((! $cat->package_weight_net || $cat->package_weight_net <= 0)
            && $cat->package_weight_gross && $cat->package_unit) {
            $cat->calculateNetWeight();
        }
        // Kalkulasi harga komparasi per kg
        if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
            $cat->comparison_price_per_kg = $cat->purchase_price / $cat->package_weight_net;
        }

        $cat->save();

        return redirect()->route('cats.index')
            ->with('success', 'cat berhasil ditambahkan!');
    }

    public function show(Cat $cat)
    {
        return view('cats.show', compact('cat'));
    }

    public function edit(Cat $cat)
    {
        $units = Unit::orderBy('code')->get();

        return view('cats.edit', compact('cat', 'units'));
    }

    public function update(Request $request, Cat $cat)
    {
        $request->validate([
            'cat_name' => 'nullable|string|max:255',
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

        // Auto-generate cat_name jika kosong
        if (empty($data['cat_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['color_name'] ?? '',
                ($data['volume'] ?? '') . ($data['volume_unit'] ?? '')
            ]);
            $data['cat_name'] = implode(' ', $parts) ?: 'Cat';
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($cat->photo) {
                    Storage::disk('public')->delete($cat->photo);
                }

                $filename = time().'_'.$photo->getClientOriginalName();
                // Simpan ke disk 'public' agar dapat diakses via /storage
                $path = $photo->storeAs('cats', $filename, 'public');
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

        // Update cat
        $cat->update($data);

        // Jika berat bersih belum diisi, hitung dari (berat kotor - berat kemasan unit)
        if ((! $cat->package_weight_net || $cat->package_weight_net <= 0)
            && $cat->package_weight_gross && $cat->package_unit) {
            $cat->calculateNetWeight();
        }
        // Kalkulasi harga komparasi per kg
        if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
            $cat->comparison_price_per_kg = $cat->purchase_price / $cat->package_weight_net;
        } else {
            $cat->comparison_price_per_kg = null;
        }

        $cat->save();

        return redirect()->route('cats.index')
            ->with('success', 'cat berhasil diupdate!');
    }

    public function destroy(Cat $cat)
    {
        // Hapus foto
        if ($cat->photo) {
            Storage::disk('public')->delete($cat->photo);
        }

        $cat->delete();

        return redirect()->route('cats.index')
            ->with('success', 'Cat berhasil dihapus!');
    }

    // API untuk mendapatkan unique values per field
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'cat_name', 'type', 'brand', 'sub_brand', 'color_code', 'color_name',
            'form', 'volume_unit', 'store', 'short_address', 'address', 'price_unit',
        ];

        if (! in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Cat::query()
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
