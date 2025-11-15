<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrickController extends Controller
{
    public function index(Request $request)
    {
        $query = Brick::query();

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('form', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('short_address', 'like', "%{$search}%");
            });
        }

        $bricks = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('bricks.index', compact('bricks'));
    }

    public function create()
    {
        return view('bricks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'price_per_piece' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();
        $data['material_name'] = 'Bata'; // Selalu "Bata"

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('bricks', $filename, 'public');
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

        // Buat brick
        $brick = Brick::create($data);

        // Kalkulasi volume dari dimensi
        if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
            $brick->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($brick->price_per_piece && $brick->package_volume && $brick->package_volume > 0) {
            $brick->calculateComparisonPrice();
        }

        $brick->save();

        return redirect()->route('bricks.index')
            ->with('success', 'Data Bata berhasil ditambahkan!');
    }

    public function show(Brick $brick)
    {
        return view('bricks.show', compact('brick'));
    }

    public function edit(Brick $brick)
    {
        return view('bricks.edit', compact('brick'));
    }

    public function update(Request $request, Brick $brick)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'price_per_piece' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();
        $data['material_name'] = 'Bata'; // Selalu "Bata"

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($brick->photo) {
                    Storage::disk('public')->delete($brick->photo);
                }

                $filename = time().'_'.$photo->getClientOriginalName();
                $path = $photo->storeAs('bricks', $filename, 'public');
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

        // Update brick
        $brick->update($data);

        // Kalkulasi volume dari dimensi
        if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
            $brick->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($brick->price_per_piece && $brick->package_volume && $brick->package_volume > 0) {
            $brick->calculateComparisonPrice();
        } else {
            $brick->comparison_price_per_m3 = null;
        }

        $brick->save();

        return redirect()->route('bricks.index')
            ->with('success', 'Data Bata berhasil diupdate!');
    }

    public function destroy(Brick $brick)
    {
        // Hapus foto
        if ($brick->photo) {
            Storage::disk('public')->delete($brick->photo);
        }

        $brick->delete();

        return redirect()->route('bricks.index')
            ->with('success', 'Data Bata berhasil dihapus!');
    }

    /**
     * API untuk mendapatkan unique values per field
     */
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'type', 'brand', 'form', 'store', 'short_address', 'address'
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Brick::query()
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