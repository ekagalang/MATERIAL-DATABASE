<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialSetting;
use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        // Load ALL materials data (not filtered)
        // JavaScript will handle showing/hiding tabs based on checkbox
        $allSettings = MaterialSetting::orderBy('display_order')->get();

        $materials = [];
        $grandTotal = 0;

        foreach ($allSettings as $setting) {
            $type = $setting->material_type;
            
            // Get model for count
            $model = null;
            switch ($type) {
                case 'brick': $model = Brick::class; break;
                case 'cat': $model = Cat::class; break;
                case 'cement': $model = Cement::class; break;
                case 'sand': $model = Sand::class; break;
            }
            
            $dbCount = $model ? $model::count() : 0;
            $grandTotal += $dbCount;

            // Get active letters for this material type
            $activeLetters = $this->getActiveLetters($type);

            // Determine active letter independently for each tab
            // Use query param like 'brick_letter', 'cat_letter'
            $letterParam = $type . '_letter';

            if ($request->has($letterParam)) {
                $currentLetter = $request->get($letterParam);
            } else {
                $currentLetter = !empty($activeLetters) ? $activeLetters[0] : 'A';
            }

            $data = $this->getMaterialData($type, $request, $currentLetter);

            if ($data) {
                $materials[] = [
                    'type' => $type,
                    'label' => MaterialSetting::getMaterialLabel($type),
                    'data' => $data,
                    'count' => $data->total(), // Filtered count
                    'db_count' => $dbCount,   // Absolute total for this type
                    'active_letters' => $activeLetters,
                    'current_letter' => $currentLetter,
                ];
            }
        }

        return view('materials.index', compact('materials', 'allSettings', 'grandTotal'));
    }

    private function getActiveLetters($type)
    {
        $model = null;
        switch ($type) {
            case 'brick':
                $model = Brick::class;
                break;
            case 'cat':
                $model = Cat::class;
                break;
            case 'cement':
                $model = Cement::class;
                break;
            case 'sand':
                $model = Sand::class;
                break;
        }

        if (!$model) {
            return [];
        }

        // Get distinct first letters of brand, uppercase
        return $model
            ::selectRaw('DISTINCT UPPER(SUBSTRING(brand, 1, 1)) as letter')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->orderBy('letter')
            ->pluck('letter')
            ->toArray();
    }

    private function getMaterialData($type, $request, $letter = 'A')
    {
        $search = $request->get('search');

        $query = null;

        switch ($type) {
            case 'brick':
                $query = Brick::query();
                break;
            case 'cat':
                $query = Cat::query()->with('packageUnit');
                break;
            case 'cement':
                $query = Cement::query()->with('packageUnit');
                break;
            case 'sand':
                $query = Sand::query()->with('packageUnit');
                break;
        }

        if (!$query) {
            return null;
        }

        // Apply Search (Overrides Letter filter usually, or combines? Standard is Search OR Letter.
        // If Search is present, ignore letter. If no search, use Letter.)
        if ($search) {
            $query->where(function ($q) use ($search, $type) {
                $q->where('brand', 'like', "%{$search}%")->orWhere('store', 'like', "%{$search}%");

                // Add specific fields based on type
                if ($type == 'brick') {
                    $q->orWhere('type', 'like', "%{$search}%");
                }
                if ($type == 'cat') {
                    $q->orWhere('cat_name', 'like', "%{$search}%");
                }
                if ($type == 'cement') {
                    $q->orWhere('cement_name', 'like', "%{$search}%");
                }
                if ($type == 'sand') {
                    $q->orWhere('sand_name', 'like', "%{$search}%");
                }
            });
        } else {
            // Apply Letter Filter (Default 'A')
            $query->where('brand', 'like', $letter . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate(10, ['*'], $type . '_page');
    }
}
