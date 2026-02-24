<?php

namespace App\Http\Controllers;

use App\Models\WorkFloor;
use Illuminate\Http\Request;

class WorkFloorController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkFloor::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', '%' . $search . '%');
        }

        $floors = $query->orderBy('name')->paginate(20)->appends($request->query());

        return view('settings.work_floors.index', compact('floors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_floors,name',
        ]);

        WorkFloor::create($validated);

        return redirect()->route('settings.work-floors.index')->with('success', 'Lantai berhasil ditambahkan.');
    }

    public function update(Request $request, WorkFloor $workFloor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_floors,name,' . $workFloor->id,
        ]);

        $workFloor->update($validated);

        return redirect()->route('settings.work-floors.index')->with('success', 'Lantai berhasil diperbarui.');
    }

    public function destroy(WorkFloor $workFloor)
    {
        $workFloor->delete();

        return redirect()->route('settings.work-floors.index')->with('success', 'Lantai berhasil dihapus.');
    }
}

