<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use App\Models\Nat;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaterialCalculationPageController extends MaterialCalculationController
{
    public function log(Request $request)
    {
        // Prepare filters array from request
        $filters = [
            'search' => $request->input('search'),
            'work_type' => $request->input('work_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        // Get paginated calculations from repository
        $calculations = $this->calculationRepository->getCalculationLog($filters, 15);

        // Append query parameters to pagination links
        $calculations->appends($request->query());

        // Get available formulas from Formula Registry
        $availableFormulas = FormulaRegistry::all();

        return view('material_calculations.log', compact('calculations', 'availableFormulas'));
    }

    public function indexRedirect()
    {
        $cacheKey = session('material_calc_last_key');
        if ($cacheKey) {
            $cachedPayload = Cache::get($cacheKey);
            if (is_array($cachedPayload)) {
                return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
            }
        }

        return redirect()->route('material-calculations.create');
    }

    public function create(Request $request)
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::query()->orderBy('brand')->get();
        $nats = Nat::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();

        // Get distinct ceramic types and sizes for filters
        $ceramicTypes = Ceramic::whereNotNull('type')->distinct()->pluck('type')->filter()->sort()->values();

        $ceramicSizes = Ceramic::whereNotNull('dimension_length')
            ->whereNotNull('dimension_width')
            ->where('dimension_length', '>', 0)
            ->where('dimension_width', '>', 0)
            ->select('dimension_length', 'dimension_width')
            ->distinct()
            ->get()
            ->map(function ($ceramic) {
                // Format as "30x30" or "20x25"
                $length = (int) $ceramic->dimension_length;
                $width = (int) $ceramic->dimension_width;

                return min($length, $width) . 'x' . max($length, $width);
            })
            ->unique()
            ->sort()
            ->values();

        $defaultInstallationType = BrickInstallationType::getDefault();
        $defaultMortarFormula = $this->getPreferredMortarFormula();

        // LOGIC BARU: Handle Multi-Select Bricks dari Price Analysis
        // Kita kirim variable $selectedBricks ke View
        $selectedBricks = collect();
        if ($request->has('brick_ids')) {
            $selectedBricks = Brick::whereIn('id', $request->brick_ids)->get();
        }

        // Check availability of 'best' recommendations per work type
        $bestRecommendations = RecommendedCombination::where('type', 'best')
            ->select('work_type')
            ->distinct()
            ->pluck('work_type')
            ->toArray();

        return view(
            'material_calculations.create',
            compact(
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'bricks',
                'cements',
                'nats',
                'sands',
                'cats',
                'ceramics',
                'ceramicTypes',
                'ceramicSizes',
                'defaultInstallationType',
                'defaultMortarFormula',
                'selectedBricks',
                'bestRecommendations',
            ),
        );
    }

    public function showPreview(string $cacheKey)
    {
        \Log::info('showPreview called', ['cacheKey' => $cacheKey]);

        if (!str_starts_with($cacheKey, self::CALCULATION_CACHE_KEY_PREFIX)) {
            \Log::warning('Rejected stale preview cache key prefix', ['cacheKey' => $cacheKey]);

            return redirect()
                ->route('material-calculations.create')
                ->with('error', 'Hasil preview lama terdeteksi. Silakan hitung ulang untuk data terbaru.');
        }

        $cachedPayload = Cache::get($cacheKey);

        \Log::info('Cache check', [
            'exists' => $cachedPayload !== null,
            'isArray' => is_array($cachedPayload),
            'keys' => $cachedPayload ? array_keys($cachedPayload) : [],
        ]);

        if (!$cachedPayload || !is_array($cachedPayload)) {
            \Log::warning('Cache not found or invalid', ['cacheKey' => $cacheKey]);

            return redirect()
                ->route('material-calculations.create')
                ->with('error', 'Hasil perhitungan tidak ditemukan atau sudah kadaluarsa. Silakan hitung ulang.');
        }

        \Log::info('Rendering preview_combinations view', [
            'hasProjects' => !empty($cachedPayload['projects'] ?? []),
            'hasCeramicProjects' => !empty($cachedPayload['ceramicProjects'] ?? []),
            'isMultiCeramic' => $cachedPayload['isMultiCeramic'] ?? false,
            'isBundle' => $cachedPayload['is_bundle'] ?? false,
        ]);

        return view('material_calculations.preview_combinations', $cachedPayload);
    }

    public function show(BrickCalculation $materialCalculation)
    {
        $materialCalculation->load([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
            'cat',
            'ceramic',
            'nat',
        ]);
        $summary = $materialCalculation->getSummary();

        return view('material_calculations.show_log', compact('materialCalculation', 'summary'));
    }

    public function edit(BrickCalculation $materialCalculation)
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        return view(
            'material_calculations.edit',
            compact(
                'materialCalculation',
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'bricks',
                'cements',
                'sands',
            ),
        );
    }

    public function exportPdf(BrickCalculation $materialCalculation)
    {
        return redirect()->back()->with('info', 'Fitur export PDF akan ditambahkan di fase berikutnya');
    }
}
