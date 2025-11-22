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
        // Get visible materials based on settings
        $visibleMaterials = MaterialSetting::getVisibleMaterials();

        $materials = [];

        foreach ($visibleMaterials as $setting) {
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

        return view('materials.index', compact('materials'));
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
                return $query->orderBy('created_at', 'desc')->paginate(10);

            case 'cat':
                $query = Cat::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cat_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10);

            case 'cement':
                $query = Cement::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cement_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10);

            case 'sand':
                $query = Sand::query()->with('packageUnit');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('sand_name', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%")
                          ->orWhere('store', 'like', "%{$search}%");
                    });
                }
                return $query->orderBy('created_at', 'desc')->paginate(10);

            default:
                return null;
        }
    }

    public function settings()
    {
        $settings = MaterialSetting::orderBy('display_order')->get();
        return view('materials.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:material_settings,id',
            'settings.*.is_visible' => 'required|boolean',
            'settings.*.display_order' => 'required|integer',
        ]);

        foreach ($request->settings as $settingData) {
            MaterialSetting::where('id', $settingData['id'])
                ->update([
                    'is_visible' => $settingData['is_visible'],
                    'display_order' => $settingData['display_order'],
                ]);
        }

        return redirect()->route('materials.settings')
            ->with('success', 'Pengaturan material berhasil diupdate!');
    }
}
