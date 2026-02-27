<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreSearchRadiusSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.store_search_radius.index', [
            'projectStoreRadiusDefaultKm' => AppSetting::getFloat('project_store_radius_default_km', 10.0),
            'projectStoreRadiusFinalKm' => AppSetting::getFloat('project_store_radius_final_km', 15.0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_store_radius_default_km' => 'required|numeric|min:0.1|max:1000',
            'project_store_radius_final_km' => 'required|numeric|min:0.1|max:1000|gte:project_store_radius_default_km',
        ]);

        $defaultKm = (float) $validated['project_store_radius_default_km'];
        $finalKm = (float) $validated['project_store_radius_final_km'];

        AppSetting::putValue('project_store_radius_default_km', $defaultKm);
        AppSetting::putValue('project_store_radius_final_km', max($finalKm, $defaultKm));

        return redirect()
            ->route('settings.store-search-radius.index')
            ->with('success', 'Setting radius pencarian toko berhasil diperbarui.');
    }
}
