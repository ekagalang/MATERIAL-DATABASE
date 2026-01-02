<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the dashboard
     * Now using DashboardService for cleaner code
     */
    public function index()
    {
        // Get all dashboard data from service
        $data = $this->dashboardService->getDashboardData();

        return view('dashboard', $data);
    }
}
