@extends('layouts.app')

@section('title', 'Analytics - ' . $formula['name'])

@section('content')
<div class="analytics-container" style="background: #f8fafc; min-height: 100vh; padding: 32px 0;">
    <!-- Header -->
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="analytics-title" style="color: #1e293b; font-size: 28px; font-weight: 700; margin-bottom: 4px; letter-spacing: -0.02em;">
                    Analytics Dashboard
                </h1>
                <p class="analytics-subtitle" style="color: #64748b; font-size: 14px; margin: 0;">
                    {{ $formula['name'] }}
                </p>
            </div>
            <a href="{{ route('work-items.index') }}" class="btn-back" style="background: white; color: #475569; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #e2e8f0; transition: all 0.2s;">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if($analytics['total_calculations'] > 0)
        <!-- Key Metrics -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="metric-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            Total Calculations
                        </div>
                        <div style="color: #0f172a; font-size: 32px; font-weight: 700; margin-bottom: 4px;">
                            {{ number_format($analytics['total_calculations']) }}
                        </div>
                        <div style="color: #94a3b8; font-size: 13px;">
                            Recorded entries
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            Total Area
                        </div>
                        <div style="color: #0f172a; font-size: 32px; font-weight: 700; margin-bottom: 4px;">
                            @format($analytics['total_area'])
                        </div>
                        <div style="color: #94a3b8; font-size: 13px;">
                            Square meters (M²)
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            Total Material Cost
                        </div>
                        <div style="color: #0f172a; font-size: 28px; font-weight: 700; margin-bottom: 4px;">
                            Rp {{ number_format($analytics['total_brick_cost'] + $analytics['total_cement_cost'] + $analytics['total_sand_cost'], 0, ',', '.') }}
                        </div>
                        <div style="color: #94a3b8; font-size: 13px;">
                            Cumulative total
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                            Average Cost per M²
                        </div>
                        <div style="color: #0f172a; font-size: 28px; font-weight: 700; margin-bottom: 4px;">
                            Rp {{ number_format($analytics['avg_cost_per_m2'], 0, ',', '.') }}
                        </div>
                        <div style="color: #94a3b8; font-size: 13px;">
                            Per square meter
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="container mb-4">
            <div class="row g-4">
                <!-- Monthly Trends Line Chart -->
                <div class="col-md-8">
                    <div class="chart-card" style="background: white; border: 1px solid #e2e8f0; padding: 28px; border-radius: 12px;">
                        <div class="chart-header" style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Monthly Calculation Trends
                            </h3>
                            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                                Overview of calculations and area over time
                            </p>
                        </div>
                        <div style="position: relative; height: 340px;">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Cost Breakdown Doughnut Chart -->
                <div class="col-md-4">
                    <div class="chart-card" style="background: white; border: 1px solid #e2e8f0; padding: 28px; border-radius: 12px;">
                        <div class="chart-header" style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Cost Distribution
                            </h3>
                            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                                Material cost breakdown
                            </p>
                        </div>
                        <div style="position: relative; height: 240px; margin-bottom: 20px;">
                            <canvas id="costBreakdownChart"></canvas>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #f8fafc; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></div>
                                    <span style="font-size: 13px; color: #475569; font-weight: 500;">Brick</span>
                                </div>
                                <span style="font-size: 13px; color: #0f172a; font-weight: 600;">{{ number_format(($analytics['total_brick_cost'] / max(1, $analytics['total_brick_cost'] + $analytics['total_cement_cost'] + $analytics['total_sand_cost'])) * 100, 1) }}%</span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #f8fafc; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 8px; height: 8px; background: #8b5cf6; border-radius: 50%;"></div>
                                    <span style="font-size: 13px; color: #475569; font-weight: 500;">Cement</span>
                                </div>
                                <span style="font-size: 13px; color: #0f172a; font-weight: 600;">{{ number_format(($analytics['total_cement_cost'] / max(1, $analytics['total_brick_cost'] + $analytics['total_cement_cost'] + $analytics['total_sand_cost'])) * 100, 1) }}%</span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: #f8fafc; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 8px; height: 8px; background: #06b6d4; border-radius: 50%;"></div>
                                    <span style="font-size: 13px; color: #475569; font-weight: 500;">Sand</span>
                                </div>
                                <span style="font-size: 13px; color: #0f172a; font-weight: 600;">{{ number_format(($analytics['total_sand_cost'] / max(1, $analytics['total_brick_cost'] + $analytics['total_cement_cost'] + $analytics['total_sand_cost'])) * 100, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Area & Cost Analysis -->
        <div class="container mb-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="chart-card" style="background: white; border: 1px solid #e2e8f0; padding: 28px; border-radius: 12px;">
                        <div class="chart-header" style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Top 10 Area Calculations
                            </h3>
                            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                                Largest calculation areas in M²
                            </p>
                        </div>
                        <div style="position: relative; height: 320px;">
                            <canvas id="areaDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="chart-card" style="background: white; border: 1px solid #e2e8f0; padding: 28px; border-radius: 12px;">
                        <div class="chart-header" style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Monthly Cost Trends
                            </h3>
                            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                                Material costs over time
                            </p>
                        </div>
                        <div style="position: relative; height: 320px;">
                            <canvas id="monthlyCostChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Usage Statistics -->
        <div class="container">
            <div class="section-header" style="margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">
                    Material Usage Statistics
                </h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                    Most frequently used materials in calculations
                </p>
            </div>

            <div class="row g-4">
                <!-- Brick Statistics -->
                <div class="col-md-4">
                    <div class="material-stats-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                            <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bricks" style="color: #3b82f6; font-size: 20px;"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">Brick Usage</h3>
                                <p style="font-size: 12px; color: #64748b; margin: 0;">Top materials</p>
                            </div>
                        </div>
                        @if(count($analytics['brick_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                @foreach($analytics['brick_counts'] as $brand => $data)
                                    <div style="padding: 14px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #3b82f6;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 600; color: #0f172a; font-size: 13px;">{{ $brand }}</span>
                                            <span style="background: #3b82f6; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; line-height: 1.5;">
                                            <div>{{ $data['brick']->type ?? '-' }}</div>
                                            <div>{{ $data['brick']->dimension_length }}×{{ $data['brick']->dimension_width }}×{{ $data['brick']->dimension_height }} cm</div>
                                            <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">Rp {{ number_format($data['brick']->price_per_piece, 0, ',', '.') }}/pc</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center" style="color: #94a3b8; padding: 20px;">No data available</div>
                        @endif
                    </div>
                </div>

                <!-- Cement Statistics -->
                <div class="col-md-4">
                    <div class="material-stats-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                            <div style="width: 40px; height: 40px; background: #f5f3ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bucket-fill" style="color: #8b5cf6; font-size: 20px;"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">Cement Usage</h3>
                                <p style="font-size: 12px; color: #64748b; margin: 0;">Top materials</p>
                            </div>
                        </div>
                        @if(count($analytics['cement_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                @foreach($analytics['cement_counts'] as $brand => $data)
                                    <div style="padding: 14px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #8b5cf6;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 600; color: #0f172a; font-size: 13px;">{{ $brand }}</span>
                                            <span style="background: #8b5cf6; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; line-height: 1.5;">
                                            <div>{{ $data['cement']->type ?? '-' }}</div>
                                            <div>{{ $data['cement']->package_weight_net }} Kg / {{ $data['cement']->package_unit }}</div>
                                            <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">Rp {{ number_format($data['cement']->package_price, 0, ',', '.') }}/{{ $data['cement']->package_unit }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center" style="color: #94a3b8; padding: 20px;">No data available</div>
                        @endif
                    </div>
                </div>

                <!-- Sand Statistics -->
                <div class="col-md-4">
                    <div class="material-stats-card" style="background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                            <div style="width: 40px; height: 40px; background: #ecfeff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-cone-striped" style="color: #06b6d4; font-size: 20px;"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">Sand Usage</h3>
                                <p style="font-size: 12px; color: #64748b; margin: 0;">Top materials</p>
                            </div>
                        </div>
                        @if(count($analytics['sand_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                @foreach($analytics['sand_counts'] as $brand => $data)
                                    <div style="padding: 14px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #06b6d4;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 600; color: #0f172a; font-size: 13px;">{{ $brand }}</span>
                                            <span style="background: #06b6d4; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; line-height: 1.5;">
                                            <div>{{ $data['sand']->type ?? '-' }}</div>
                                            <div>{{ $data['sand']->sand_name ?? '-' }}</div>
                                            <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">Rp {{ number_format($data['sand']->package_price, 0, ',', '.') }}/{{ $data['sand']->package_unit }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center" style="color: #94a3b8; padding: 20px;">No data available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container">
            <div style="background: white; border: 1px solid #e2e8f0; padding: 80px 40px; text-align: center; border-radius: 12px;">
                <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class="bi bi-graph-up" style="font-size: 36px; color: #94a3b8;"></i>
                </div>
                <h3 style="color: #0f172a; font-size: 20px; font-weight: 700; margin-bottom: 8px;">No Analytics Data Available</h3>
                <p style="color: #64748b; font-size: 14px; margin: 0; max-width: 400px; margin: 0 auto;">
                    There are no calculations saved for this work item yet. Start calculating to view material analytics.
                </p>
            </div>
        </div>
    @endif
</div>

@if($analytics['total_calculations'] > 0)
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .metric-card {
        transition: all 0.2s ease;
    }

    .metric-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .btn-back:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .chart-card {
        transition: all 0.2s ease;
    }

    .chart-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
</style>

<script>
    // Chart.js configuration
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.display = false;

    // Corporate color palette
    const colors = {
        primary: '#3b82f6',
        secondary: '#8b5cf6',
        tertiary: '#06b6d4',
        grid: '#e2e8f0',
        text: '#475569'
    };

    // Monthly Trends Line Chart
    const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    new Chart(monthlyTrendsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['monthly_trends']['labels'] ?? []) !!},
            datasets: [
                {
                    label: 'Calculations',
                    data: {!! json_encode($analytics['monthly_trends']['calculations'] ?? []) !!},
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Area (M²)',
                    data: {!! json_encode($analytics['monthly_trends']['areas'] ?? []) !!},
                    borderColor: colors.secondary,
                    backgroundColor: 'rgba(139, 92, 246, 0.05)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: colors.secondary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        padding: 16,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: colors.text
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    borderColor: '#334155',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    border: { display: false },
                    ticks: { font: { size: 11 }, color: colors.text }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 11 }, color: colors.text }
                }
            }
        }
    });

    // Cost Breakdown Doughnut Chart
    const costBreakdownCtx = document.getElementById('costBreakdownChart').getContext('2d');
    new Chart(costBreakdownCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($analytics['cost_breakdown_labels']) !!},
            datasets: [{
                data: {!! json_encode($analytics['cost_breakdown_data']) !!},
                backgroundColor: [colors.primary, colors.secondary, colors.tertiary],
                borderColor: '#fff',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed || 0;
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Area Distribution Bar Chart
    const areaDistributionCtx = document.getElementById('areaDistributionChart').getContext('2d');
    const areaData = {!! json_encode($analytics['area_distribution'] ?? []) !!};
    new Chart(areaDistributionCtx, {
        type: 'bar',
        data: {
            labels: areaData.map(item => item.label),
            datasets: [{
                label: 'Area (M²)',
                data: areaData.map(item => item.area),
                backgroundColor: colors.primary,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => 'Area: ' + context.parsed.y.toFixed(2) + ' M²'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    border: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: colors.text,
                        callback: (value) => value.toFixed(0) + ' M²'
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 10 }, color: colors.text }
                }
            }
        }
    });

    // Monthly Cost Chart
    const monthlyCostCtx = document.getElementById('monthlyCostChart').getContext('2d');
    new Chart(monthlyCostCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['monthly_trends']['labels'] ?? []) !!},
            datasets: [{
                label: 'Cost (Rp)',
                data: {!! json_encode($analytics['monthly_trends']['costs'] ?? []) !!},
                backgroundColor: colors.tertiary,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    borderColor: '#334155',
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => 'Cost: Rp ' + context.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    border: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: colors.text,
                        callback: (value) => 'Rp ' + (value / 1000000).toFixed(1) + 'M'
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 11 }, color: colors.text }
                }
            }
        }
    });
</script>
@endif
@endsection
