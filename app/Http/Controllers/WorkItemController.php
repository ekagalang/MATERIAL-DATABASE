<?php

namespace App\Http\Controllers;

use App\Models\WorkItem;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;

class WorkItemController extends Controller
{
    /**
     * Display a listing of the resource.
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

        // Generate analytics untuk setiap formula
        $analytics = [];
        foreach ($formulas as $formula) {
            $workType = $formula['code'];

            // Get all calculations untuk work_type ini
            $calculations = \App\Models\BrickCalculation::where('calculation_params->work_type', $workType)->get();

            $totalCalculations = $calculations->count();

            if ($totalCalculations > 0) {
                // Hitung dominan material
                $brickCounts = [];
                $cementCounts = [];
                $sandCounts = [];

                // Hitung total cost dan area untuk avg cost per M2
                $totalCost = 0;
                $totalArea = 0;

                foreach ($calculations as $calc) {
                    // Count Bricks
                    if ($calc->brick) {
                        $brickKey = $calc->brick->brand;
                        $brickCounts[$brickKey] = ($brickCounts[$brickKey] ?? 0) + 1;
                    }

                    // Count Cement
                    if ($calc->cement) {
                        $cementKey = $calc->cement->brand;
                        $cementCounts[$cementKey] = ($cementCounts[$cementKey] ?? 0) + 1;
                    }

                    // Count Sand
                    if ($calc->sand) {
                        $sandKey = $calc->sand->brand;
                        $sandCounts[$sandKey] = ($sandCounts[$sandKey] ?? 0) + 1;
                    }

                    // Sum total cost and area
                    $totalCost += $calc->total_material_cost ?? 0;
                    $totalArea += $calc->wall_area ?? 0;
                }

                // Sort dan ambil top 3
                arsort($brickCounts);
                arsort($cementCounts);
                arsort($sandCounts);

                // Calculate average cost per M2
                $avgCostPerM2 = $totalArea > 0 ? $totalCost / $totalArea : 0;

                $analytics[$workType] = [
                    'total' => $totalCalculations,
                    'avg_cost_per_m2' => $avgCostPerM2,
                    'total_area' => $totalArea,
                    'top_bricks' => array_slice($brickCounts, 0, 3, true),
                    'top_cements' => array_slice($cementCounts, 0, 3, true),
                    'top_sands' => array_slice($sandCounts, 0, 3, true),
                ];
            } else {
                $analytics[$workType] = [
                    'total' => 0,
                    'avg_cost_per_m2' => 0,
                    'total_area' => 0,
                    'top_bricks' => [],
                    'top_cements' => [],
                    'top_sands' => [],
                ];
            }
        }

        return view('work-items.index', compact('workItems', 'formulas', 'analytics'));
    }

    /**
     * Display analytics for specific work item
     */
    public function analytics($code)
    {
        // Get formula info
        $formulas = FormulaRegistry::all();
        $formula = collect($formulas)->firstWhere('code', $code);

        if (!$formula) {
            abort(404, 'Formula tidak ditemukan');
        }

        // Get all calculations untuk work_type ini
        $calculations = \App\Models\BrickCalculation::where('calculation_params->work_type', $code)
            ->with(['brick', 'cement', 'sand'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCalculations = $calculations->count();

        // Hitung dominan material
        $brickCounts = [];
        $cementCounts = [];
        $sandCounts = [];

        // Detailed stats
        $totalBrickCost = 0;
        $totalCementCost = 0;
        $totalSandCost = 0;
        $totalArea = 0;

        foreach ($calculations as $calc) {
            // Count Bricks
            if ($calc->brick) {
                $brickKey = $calc->brick->brand;
                if (!isset($brickCounts[$brickKey])) {
                    $brickCounts[$brickKey] = [
                        'count' => 0,
                        'brick' => $calc->brick,
                    ];
                }
                $brickCounts[$brickKey]['count']++;
            }

            // Count Cement
            if ($calc->cement) {
                $cementKey = $calc->cement->brand;
                if (!isset($cementCounts[$cementKey])) {
                    $cementCounts[$cementKey] = [
                        'count' => 0,
                        'cement' => $calc->cement,
                    ];
                }
                $cementCounts[$cementKey]['count']++;
            }

            // Count Sand
            if ($calc->sand) {
                $sandKey = $calc->sand->brand;
                if (!isset($sandCounts[$sandKey])) {
                    $sandCounts[$sandKey] = [
                        'count' => 0,
                        'sand' => $calc->sand,
                    ];
                }
                $sandCounts[$sandKey]['count']++;
            }

            // Sum costs and area
            $totalBrickCost += $calc->brick_total_cost ?? 0;
            $totalCementCost += $calc->cement_total_cost ?? 0;
            $totalSandCost += $calc->sand_total_cost ?? 0;
            $totalArea += $calc->wall_area ?? 0;
        }

        // Sort by count descending
        uasort($brickCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($cementCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($sandCounts, fn($a, $b) => $b['count'] <=> $a['count']);

        $analytics = [
            'total_calculations' => $totalCalculations,
            'total_brick_cost' => $totalBrickCost,
            'total_cement_cost' => $totalCementCost,
            'total_sand_cost' => $totalSandCost,
            'total_area' => $totalArea,
            'avg_cost_per_m2' =>
                $totalArea > 0 ? ($totalBrickCost + $totalCementCost + $totalSandCost) / $totalArea : 0,
            'brick_counts' => $brickCounts,
            'cement_counts' => $cementCounts,
            'sand_counts' => $sandCounts,
        ];

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
