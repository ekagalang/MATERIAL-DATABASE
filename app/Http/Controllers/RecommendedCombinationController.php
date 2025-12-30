<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\Cement;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendedCombinationController extends Controller
{
    public function index()
    {
        // Get existing recommendations grouped by work_type
        $recommendations = RecommendedCombination::where('type', 'best')->orderBy('work_type')->get();

        // Group recommendations by work_type
        $groupedRecommendations = $recommendations->groupBy('work_type');

        // Get options for dropdowns
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        // Get available formulas (work types)
        $formulas = FormulaRegistry::all();

        return view(
            'settings.recommendations.index',
            compact('groupedRecommendations', 'bricks', 'cements', 'sands', 'formulas'),
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
                // Skip if work_type, brick_id, cement_id, or sand_id are empty
                if (
                    empty($rec['work_type']) ||
                    empty($rec['brick_id']) ||
                    empty($rec['cement_id']) ||
                    empty($rec['sand_id'])
                ) {
                    continue;
                }

                $dataToInsert[] = [
                    'work_type' => $rec['work_type'],
                    'brick_id' => $rec['brick_id'],
                    'cement_id' => $rec['cement_id'],
                    'sand_id' => $rec['sand_id'],
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
