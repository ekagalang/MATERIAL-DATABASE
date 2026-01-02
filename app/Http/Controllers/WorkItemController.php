<?php

namespace App\Http\Controllers;

use App\Models\WorkItem;
use App\Services\Analytics\WorkItemAnalyticsService;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;

class WorkItemController extends Controller
{
    protected WorkItemAnalyticsService $analyticsService;

    public function __construct(WorkItemAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display a listing of the resource.
     * Now using WorkItemAnalyticsService for cleaner code
     */
    public function index(Request $request)
    {
        $query = WorkItem::query();

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $workItems = $query->orderBy($sortBy, $sortDirection)->paginate(20)->appends($request->query());

        // Ambil daftar formula/service yang tersedia
        $formulas = FormulaRegistry::all();

        // Generate analytics menggunakan service
        $analytics = $this->analyticsService->generateAllAnalytics($formulas);

        return view('work-items.index', compact('workItems', 'formulas', 'analytics'));
    }

    /**
     * Display analytics for specific work item
     * Now using WorkItemAnalyticsService for cleaner code
     */
    public function analytics($code)
    {
        // Get formula info
        $formulas = FormulaRegistry::all();
        $formula = collect($formulas)->firstWhere('code', $code);

        if (!$formula) {
            abort(404, 'Formula tidak ditemukan');
        }

        // Generate detailed analytics menggunakan service
        $analyticsData = $this->analyticsService->generateDetailedAnalytics($code);

        // Extract data for view
        $analytics = [
            'total_calculations' => $analyticsData['total_calculations'],
            'total_brick_cost' => $analyticsData['total_brick_cost'],
            'total_cement_cost' => $analyticsData['total_cement_cost'],
            'total_sand_cost' => $analyticsData['total_sand_cost'],
            'total_area' => $analyticsData['total_area'],
            'avg_cost_per_m2' => $analyticsData['avg_cost_per_m2'],
            'brick_counts' => $analyticsData['brick_counts'],
            'cement_counts' => $analyticsData['cement_counts'],
            'sand_counts' => $analyticsData['sand_counts'],
        ];

        $calculations = $analyticsData['calculations'];

        return view('work-items.analytics', compact('formula', 'analytics', 'calculations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('work-items.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        WorkItem::create($request->all());

        return redirect()->route('work-items.index')->with('success', 'Item Pekerjaan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkItem $workItem)
    {
        return view('work-items.show', compact('workItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkItem $workItem)
    {
        return view('work-items.edit', compact('workItem'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkItem $workItem)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $workItem->update($request->all());

        return redirect()->route('work-items.index')->with('success', 'Item Pekerjaan berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkItem $workItem)
    {
        $workItem->delete();

        return redirect()->route('work-items.index')->with('success', 'Item Pekerjaan berhasil dihapus!');
    }
}
