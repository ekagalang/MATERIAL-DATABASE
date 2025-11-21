<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use App\Helpers\MaterialTypeDetector;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        // Filter by material type
        if ($request->has('material_type') && $request->material_type != '') {
            $query->where('material_type', $request->material_type);
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = ['code', 'material_type', 'name', 'package_weight', 'created_at'];

        // Default sorting jika tidak ada atau tidak valid
        if (!$sortBy || !in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
            $sortDirection = 'asc';
            $units = $query->orderBy($sortBy, $sortDirection)->paginate(20)->appends($request->query());
        } else {
            // Validasi direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
            $units = $query->orderBy($sortBy, $sortDirection)->paginate(20)->appends($request->query());
        }

        // Get material types untuk filter dropdown
        $materialTypes = Unit::getMaterialTypesWithLabels();

        return view('units.index', compact('units', 'materialTypes'));
    }

    public function create()
    {
        // Get material types untuk dropdown
        $materialTypes = Unit::getMaterialTypesWithLabels();
        return view('units.create', compact('materialTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'material_type' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        // Validasi unique: code + material_type
        $exists = Unit::where('code', $request->code)
            ->where('material_type', $request->material_type)
            ->exists();
        
        if ($exists) {
            return back()->withErrors([
                'code' => 'Satuan dengan code "'.$request->code.'" untuk material "'.$request->material_type.'" sudah ada!'
            ])->withInput();
        }

        Unit::create($request->all());

        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil ditambahkan!');
    }

    public function edit(Unit $unit)
    {
        // Get material types untuk dropdown
        $materialTypes = Unit::getMaterialTypesWithLabels();
        return view('units.edit', compact('unit', 'materialTypes'));
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'material_type' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        // Validasi unique: code + material_type (kecuali record ini)
        $exists = Unit::where('code', $request->code)
            ->where('material_type', $request->material_type)
            ->where('id', '!=', $unit->id)
            ->exists();
        
        if ($exists) {
            return back()->withErrors([
                'code' => 'Satuan dengan code "'.$request->code.'" untuk material "'.$request->material_type.'" sudah ada!'
            ])->withInput();
        }

        $unit->update($request->all());

        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil diupdate!');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil dihapus!');
    }
}