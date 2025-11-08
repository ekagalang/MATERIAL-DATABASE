<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('code')->paginate(20);
        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        Unit::create($request->all());

        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil ditambahkan!');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'name' => 'required|string|max:100',
            'package_weight' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

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