@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Hero Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="welcome-card text-white p-5 rounded-4 shadow-lg position-relative overflow-hidden" style="background: linear-gradient(135deg, #891313 0%, #4a0404 100%);">
            <div class="position-relative z-1">
                <h1 class="fw-bold display-5 mb-2 text-shadow-bottom">Selamat Datang di Material Database</h1>
                <p class="lead mb-4 text-shadow-bottom">Kelola data material, sumber daya, dan analisis harga proyek Anda dalam satu tempat.</p>
            </div>
            <!-- Decorative Background Elements -->
            <img src="{{ asset('Logo.png') }}" alt="Logo" class="position-absolute opacity-10" style="height: 100%; width: auto; right: 0; bottom: 0; transform: rotate(0deg);">
        </div>
    </div>
</div>

<!-- Main Stats Grid (CSS Grid Layout) -->
<div class="stats-grid-container mb-5">
    <!-- 1. Total Material -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper red">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Total Material</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format($materialCount) }}</h2>
            <div class="card-meta">
                <span class="status-badge red text-shadow-bottom"><i class="bi bi-graph-up-arrow"></i> +4 Kategori</span>
            </div>
        </div>
        <div class="card-overlay red"></div>
    </div>

    <!-- 2. Database Satuan -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper cyan">
            <i class="bi bi-rulers"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Satuan Unit</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format($unitCount) }}</h2>
            <div class="card-meta">
                <span class="status-badge cyan text-shadow-bottom"><i class="bi bi-check2-circle"></i> Terstandarisasi</span>
            </div>
        </div>
        <div class="card-overlay cyan"></div>
    </div>

    <!-- 3. Database Toko -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper orange">
            <i class="bi bi-shop"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Mitra Toko</p>
            <h2 class="card-value text-shadow-bottom">--</h2>
            <div class="card-meta">
                <span class="status-badge orange text-shadow-bottom">COMING SOON</span>
            </div>
        </div>
        <div class="card-overlay orange"></div>
    </div>

    <!-- 4. Tenaga Kerja -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper green">
            <i class="bi bi-people"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Tenaga Kerja</p>
            <h2 class="card-value text-shadow-bottom">--</h2>
            <div class="card-meta">
                <span class="status-badge green text-shadow-bottom">Coming Soon</span>
            </div>
        </div>
        <div class="card-overlay green"></div>
    </div>

    <!-- 5. Item Pekerjaan -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper blue">
            <i class="bi bi-building-gear"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Item Pekerjaan</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format($workItemCount) }}</h2>
            <div class="card-meta">
                <span class="status-badge blue text-shadow-bottom">Total Rumus</span>
            </div>
        </div>
        <div class="card-overlay blue"></div>
    </div>

    <!-- 6. Keterampilan -->
    <div class="modern-stat-card">
        <div class="card-icon-wrapper purple">
            <i class="bi bi-tools"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Keterampilan</p>
            <h2 class="card-value text-shadow-bottom">--</h2>
            <div class="card-meta">
                <span class="status-badge purple text-shadow-bottom">COMING SOON</span>
            </div>
        </div>
        <div class="card-overlay purple"></div>
    </div>
</div>

<style>
    /* CSS Grid Layout - The Fix */
    .stats-grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* FORCE 3 Columns */
        gap: 24px;
    }

    /* Responsive: 2 cols on tablets, 1 col on phones */
    @media (max-width: 992px) {
        .stats-grid-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .stats-grid-container {
            grid-template-columns: 1fr;
        }
    }

    /* Modern Card Design */
    .modern-stat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 180px;
    }

    .modern-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: transparent;
    }

    .card-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .modern-stat-card:hover .card-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    /* Color Variants */
    .red i { color: #ef4444; } .red.card-icon-wrapper { background: #fee2e2; } .red.card-overlay { background: radial-gradient(circle at top right, #fee2e2 0%, transparent 70%); }
    .cyan i { color: #06b6d4; } .cyan.card-icon-wrapper { background: #cffafe; } .cyan.card-overlay { background: radial-gradient(circle at top right, #cffafe 0%, transparent 70%); }
    .orange i { color: #f97316; } .orange.card-icon-wrapper { background: #ffedd5; } .orange.card-overlay { background: radial-gradient(circle at top right, #ffedd5 0%, transparent 70%); }
    .green i { color: #10b981; } .green.card-icon-wrapper { background: #d1fae5; } .green.card-overlay { background: radial-gradient(circle at top right, #d1fae5 0%, transparent 70%); }
    .blue i { color: #3b82f6; } .blue.card-icon-wrapper { background: #dbeafe; } .blue.card-overlay { background: radial-gradient(circle at top right, #dbeafe 0%, transparent 70%); }
    .purple i { color: #8b5cf6; } .purple.card-icon-wrapper { background: #ede9fe; } .purple.card-overlay { background: radial-gradient(circle at top right, #ede9fe 0%, transparent 70%); }

    .card-label {
        font-size: 14px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        -webkit-text-stroke: 0.5px black;
    }

    .card-value {
        font-size: 32px;
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 12px;
        line-height: 1;
        -webkit-text-stroke: 0.5px black;
    }

    .card-meta {
        display: flex;
        align-items: center;
        font-size: 13px;
        font-weight: 500;
    }

    .trend.up { 
        color: #ffffff;
        -webkit-text-stroke: 0.2px black;
    }

    .trend.neutral { color: #64748b; }

    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #ffffff;
        -webkit-text-stroke: 0.2px black;
    }
    .status-badge.red { background: #fee2e2; color: #b91c1c; }
    .status-badge.cyan { background: #cffafe; color: #0e7490; }
    .status-badge.orange { background: #fff7ed; color: #c2410c; }
    .status-badge.green { background: #f0fdf4; color: #15803d; }
    .status-badge.blue { background: #eff6ff; color: #1d4ed8; }
    .status-badge.purple { background: #f5f3ff; color: #6d28d9; }

    .card-overlay {
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        opacity: 0.4;
        border-radius: 0 0 0 100%;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    .modern-stat-card:hover .card-overlay {
        opacity: 0.8;
    }
</style>

<!-- Content Grid -->
<div class="row g-4">
    <!-- Chart Section -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Distribusi Material</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light rounded-pill px-3" type="button">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div style="height: 300px; width: 100%;">
                    <canvas id="materialChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="col-lg-4">
        <!-- Recent Activity -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">Aktivitas Terakhir</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush py-2">
                    @forelse($recentActivities as $activity)
                        <div class="list-group-item border-0 px-4 py-3 d-flex align-items-center hover-bg-light transition-base">
                            <div class="avatar rounded-circle bg-{{ $activity->category_color ?? 'primary' }} bg-opacity-10 text-{{ $activity->category_color ?? 'primary' }} p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-{{ $activity->category == 'Bata' ? 'bricks' : ($activity->category == 'Cat' ? 'palette' : ($activity->category == 'Pasir' ? 'bucket' : 'box')) }}"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-0 text-truncate fw-semibold font-sans">{{ $activity->name }}</h6>
                                <small class="d-block text-shadow-bottom">
                                    Ditambahkan ke <span class="badge bg-light border">{{ $activity->category }}</span>
                                </small>
                            </div>
                            <small class="ms-2 whitespace-nowrap">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                    @empty
                        <div class="text-center py-5 text-shadow-bottom">
                            <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
                            Belum ada aktivitas
                        </div>
                    @endforelse
                </div>
            </div>
            @if($recentActivities->count() > 0)
            <div class="card-footer bg-white border-0 px-4 pb-4 pt-0">
                <a href="{{ route('materials.index') }}" class="btn btn-light w-100 fw-medium text-shadow-bottom rounded-3">Lihat Semua History</a>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('materialChart').getContext('2d');
    
    // Gradient for chart
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(137, 19, 19, 0.2)');
    gradient.addColorStop(1, 'rgba(137, 19, 19, 0)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['labels']) !!},
            datasets: [{
                label: 'Jumlah Item',
                data: {!! json_encode($chartData['data']) !!},
                backgroundColor: [
                    '#891313', // Bata
                    '#0dcaf0', // Cat (Info)
                    '#6c757d', // Semen (Secondary)
                    '#ffc107'  // Pasir (Warning)
                ],
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: "'Inter', sans-serif", size: 13 },
                    bodyFont: { family: "'Inter', sans-serif", size: 13 },
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        font: { family: "'Inter', sans-serif" },
                        color: '#64748b'
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif", weight: '500' },
                        color: '#475569'
                    },
                    border: { display: false }
                }
            }
        }
    });
});
</script>
@endpush

<style>
    /* Custom Utilities */
    .text-shadow-bottom {
        text-shadow: 0 1.1px 0 rgba(0, 0, 0, 1);
    }
    .font-sans { font-family: 'Inter', sans-serif; }
    .transition-base { transition: all 0.2s ease; }
    .hover-bg-light:hover { background-color: #f8fafc !important; }
    .btn-white-glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
    }
    .btn-white-glass:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .welcome-card {
        background-size: cover;
        background-position: center;
    }
    
    .stat-card {
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1) !important;
    }

    .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection