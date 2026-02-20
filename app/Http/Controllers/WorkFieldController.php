<?php

namespace App\Http\Controllers;

use App\Models\WorkField;
use Illuminate\Http\Request;

class WorkFieldController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkField::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', '%' . $search . '%');
        }

        $fields = $query->orderBy('name')->paginate(20)->appends($request->query());

        return view('settings.work_fields.index', compact('fields'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_fields,name',
        ]);

        WorkField::create($validated);

        return redirect()->route('settings.work-fields.index')->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function update(Request $request, WorkField $workField)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:work_fields,name,' . $workField->id,
        ]);

        $workField->update($validated);

        return redirect()->route('settings.work-fields.index')->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy(WorkField $workField)
    {
        $workField->delete();

        return redirect()->route('settings.work-fields.index')->with('success', 'Bidang berhasil dihapus.');
    }
}

