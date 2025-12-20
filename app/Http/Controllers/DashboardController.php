<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;
use App\Models\Unit;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Counts
        $counts = [
            'brick' => Brick::count(),
            'cat' => Cat::count(),
            'cement' => Cement::count(),
            'sand' => Sand::count(),
        ];

        $materialCount = array_sum($counts);
        $unitCount = Unit::count();

        // Other features (Under Dev)
        $storeCount = null;
        $workItemCount = null;
        $workerCount = null;
        $skillCount = null;

        // Recent Activity (Get last 5 items from each and sort)
        $recents = collect();

        $recents = $recents->concat(
            Brick::latest()
                ->take(3)
                ->get()
                ->map(function ($item) {
                    $item->category = 'Bata';
                    $item->category_color = 'danger'; // Bootstrap color class
                    $item->name = "{$item->brand} {$item->type}";
                    return $item;
                }),
        );

        $recents = $recents->concat(
            Cat::latest()
                ->take(3)
                ->get()
                ->map(function ($item) {
                    $item->category = 'Cat';
                    $item->category_color = 'info';
                    $item->name = "{$item->brand} {$item->color_name}";
                    return $item;
                }),
        );

        $recents = $recents->concat(
            Cement::latest()
                ->take(3)
                ->get()
                ->map(function ($item) {
                    $item->category = 'Semen';
                    $item->category_color = 'secondary';
                    $item->name = "{$item->brand} {$item->type}";
                    return $item;
                }),
        );

        $recents = $recents->concat(
            Sand::latest()
                ->take(3)
                ->get()
                ->map(function ($item) {
                    $item->category = 'Pasir';
                    $item->category_color = 'warning';
                    $item->name = "{$item->brand} {$item->type}";
                    return $item;
                }),
        );

        // Sort by created_at desc and take top 5
        $recentActivities = $recents->sortByDesc('created_at')->take(5);

        // Chart Data
        $chartData = [
            'labels' => ['Bata', 'Cat', 'Semen', 'Pasir'],
            'data' => [$counts['brick'], $counts['cat'], $counts['cement'], $counts['sand']],
        ];

        return view(
            'dashboard',
            compact(
                'materialCount',
                'unitCount',
                'storeCount',
                'workItemCount',
                'workerCount',
                'skillCount',
                'recentActivities',
                'chartData',
            ),
        );
    }
}
