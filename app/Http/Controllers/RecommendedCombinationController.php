<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendedCombinationController extends Controller
{
    public function index()
    {
        // Get existing recommendations grouped by work_type with eager loaded relationships
        $recommendations = RecommendedCombination::with(['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'])
            ->where('type', 'best')
            ->orderBy('work_type')
            ->get();

        // Group recommendations by work_type
        $groupedRecommendations = $recommendations->groupBy('work_type');

        // Get options for dropdowns
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('brand')->get();
        $nats = Cement::where('type', 'Nat')->orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();

        // Get available formulas (work types)
        $formulas = FormulaRegistry::all();

        return view(
            'settings.recommendations.index',
            compact('groupedRecommendations', 'bricks', 'cements', 'nats', 'sands', 'cats', 'ceramics', 'formulas'),
        );
    }

    public function store(Request $request)
    {
        \Log::info('Saving recommendations:', $request->input('recommendations', []));

        $request->validate([
            'recommendations' => 'nullable|array',
            'recommendations.*.work_type' => 'required|string',
            'recommendations.*.brick_id' => 'nullable|exists:bricks,id',
            'recommendations.*.cement_id' => 'nullable|exists:cements,id',
            'recommendations.*.sand_id' => 'nullable|exists:sands,id',
            'recommendations.*.cat_id' => 'nullable|exists:cats,id',
            'recommendations.*.ceramic_id' => 'nullable|exists:ceramics,id',
            'recommendations.*.nat_id' => 'nullable|exists:cements,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Delete all existing 'best' recommendations
            // This ensures that removed rows in the UI are removed from the DB
            RecommendedCombination::where('type', 'best')->delete();

            // 2. Insert new ones
            $dataToInsert = [];

            $submittedRecommendations = $request->recommendations ?? [];

            foreach ($submittedRecommendations as $rec) {
                $workType = $rec['work_type'] ?? null;
                $requiredMaterials = $workType ? FormulaRegistry::materialsFor($workType) : [];
                $requiredMaterials = array_values(array_diff($requiredMaterials, ['brick']));

                if (!$workType || empty($requiredMaterials)) {
                    continue;
                }

                $missingRequired = false;
                foreach ($requiredMaterials as $material) {
                    $key = $material . '_id';
                    if (empty($rec[$key])) {
                        $missingRequired = true;
                        break;
                    }
                }

                if ($missingRequired) {
                    continue;
                }

                $dataToInsert[] = [
                    'work_type' => $workType,
                    'brick_id' => $rec['brick_id'] ?? null,
                    'cement_id' => $rec['cement_id'] ?? null,
                    'sand_id' => $rec['sand_id'] ?? null,
                    'cat_id' => $rec['cat_id'] ?? null,
                    'ceramic_id' => $rec['ceramic_id'] ?? null,
                    'nat_id' => $rec['nat_id'] ?? null,
                    'type' => 'best',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($dataToInsert)) {
                RecommendedCombination::insert($dataToInsert);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Daftar rekomendasi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
