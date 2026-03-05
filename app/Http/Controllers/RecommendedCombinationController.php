<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Repositories\RecommendationRepository;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecommendedCombinationController extends Controller
{
    public function __construct(private RecommendationRepository $repository) {}

    public function index()
    {
        // Get existing recommendations grouped by work_type with eager loaded relationships
        $recommendations = RecommendedCombination::with(['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'])
            ->where('type', 'best')
            ->orderBy('work_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Group recommendations by work_type
        $groupedRecommendations = $recommendations
            ->groupBy('work_type')
            ->map(fn($rows) => $rows->take(RecommendationRepository::MAX_RECOMMENDATIONS_PER_WORK_TYPE)->values());

        // Get options for dropdowns
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::query()->orderBy('brand')->get();
        $nats = Nat::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();

        // Get available formulas (work types)
        $formulas = FormulaRegistry::all();
        return view(
            'settings.recommendations.index',
            compact(
                'groupedRecommendations',
                'bricks',
                'cements',
                'nats',
                'sands',
                'cats',
                'ceramics',
                'formulas',
            ),
        );
    }

    public function store(Request $request)
    {
        \Log::info('Saving recommendations:', $request->input('recommendations', []));

        $request->validate([
            'recommendations' => 'nullable|array',
            'recommendations.*.work_type' => 'required|string',
            'recommendations.*.brick_id' => 'nullable|exists:bricks,id',
            'recommendations.*.cement_id' => [
                'nullable',
                Rule::exists('cements', 'id')->where('material_kind', Cement::MATERIAL_KIND),
            ],
            'recommendations.*.sand_id' => 'nullable|exists:sands,id',
            'recommendations.*.cat_id' => 'nullable|exists:cats,id',
            'recommendations.*.ceramic_id' => 'nullable|exists:ceramics,id',
            'recommendations.*.nat_id' => [
                'nullable',
                Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND),
            ],
        ]);

        try {
            $submittedRecommendations = $request->recommendations ?? [];
            $this->repository->bulkUpdateRecommendations($submittedRecommendations);

            return redirect()->back()->with('success', 'Daftar rekomendasi berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
