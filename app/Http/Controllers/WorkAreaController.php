<?php

namespace App\Http\Controllers;

use App\Models\WorkArea;
use Illuminate\Http\Request;

class WorkAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkArea::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', '%' . $search . '%');
        }

        $areas = $query->orderBy('name')->paginate(20)->appends($request->query());

        return view('settings.work_areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_areas,name',
        ]);

        WorkArea::create($validated);

        return redirect()->route('settings.work-areas.index')->with('success', 'Area berhasil ditambahkan.');
    }

    public function update(Request $request, WorkArea $workArea)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_areas,name,' . $workArea->id,
        ]);

        $workArea->update($validated);

        return redirect()->route('settings.work-areas.index')->with('success', 'Area berhasil diperbarui.');
    }

    public function destroy(WorkArea $workArea)
    {
        $workArea->delete();

        return redirect()->route('settings.work-areas.index')->with('success', 'Area berhasil dihapus.');
    }
}

