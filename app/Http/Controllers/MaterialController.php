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

        foreach ($allSettings as $setting) {
            $type = $setting->material_type;
            $data = $this->getMaterialData($type, $request);

            if ($data) {
                $materials[] = [
                    'type' => $type,
                    'label' => MaterialSetting::getMaterialLabel($type),
                    'data' => $data,
                    'count' => $data->total(),
                ];
            }
        }

        return view('materials.index', compact('materials', 'allSettings'));
    }

    private function getMaterialData($type, $request)
    {
        $search = $request->get('search');

        switch ($type) {
            case 'brick':
                $query = Brick::query();
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('type', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10, ['*'], 'brick_page');

            case 'cat':
                $query = Cat::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cat_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10, ['*'], 'cat_page');

            case 'cement':
                $query = Cement::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cement_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10, ['*'], 'cement_page');

            case 'sand':
                $query = Sand::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('sand_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10, ['*'], 'sand_page');

            default:
                return null;
        }
    }

}
