<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use App\Helpers\MaterialTypeDetector;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query()->with('materialTypes');

        // Filter by material type
        if ($request->has('material_type') && $request->material_type != '') {
            $query->whereHas('materialTypes', function ($q) use ($request) {
                $q->where('material_type', $request->material_type);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = ['code', 'name', 'package_weight', 'created_at'];

        // Default sorting jika tidak ada atau tidak valid
        if (!$sortBy || !in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
            $sortDirection = 'asc';
        } else {
            // Validasi direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
        }

        $units = $query->orderBy($sortBy, $sortDirection)->paginate(20)->appends($request->query());

        // Get material types untuk filter dropdown
        $materialTypes = Unit::getMaterialTypesWithLabels();

        return view('units.index', compact('units', 'materialTypes'));
    }

    public function create()
    {
        // Get material types untuk checkbox
        $materialTypes = Unit::getMaterialTypesWithLabels();
        return view('units.create', compact('materialTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'material_types' => 'required|array',
            'material_types.*' => 'string',
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $unit = Unit::create([
            'code' => $request->code,
            'name' => $request->name,
            'package_weight' => $request->package_weight,
            'description' => $request->description,
        ]);

        // Simpan relasi material types
        foreach ($request->material_types as $type) {
            $unit->materialTypes()->create(['material_type' => $type]);
        }

        return redirect()->route('units.index')->with('success', 'Satuan berhasil ditambahkan!');
    }

    public function edit(Unit $unit)
    {
        // Get material types untuk checkbox
        $materialTypes = Unit::getMaterialTypesWithLabels();
        // Get selected types
        $selectedTypes = $unit->materialTypes()->pluck('material_type')->toArray();

        return view('units.edit', compact('unit', 'materialTypes', 'selectedTypes'));
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'material_types' => 'required|array',
            'material_types.*' => 'string',
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $unit->update([
            'code' => $request->code,
            'name' => $request->name,
            'package_weight' => $request->package_weight,
            'description' => $request->description,
        ]);

        // Sync material types (Hapus semua lalu insert baru)
        $unit->materialTypes()->delete();

        foreach ($request->material_types as $type) {
            $unit->materialTypes()->create(['material_type' => $type]);
        }

        return redirect()->route('units.index')->with('success', 'Satuan berhasil diupdate!');
    }
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Satuan berhasil dihapus!');
    }
}
