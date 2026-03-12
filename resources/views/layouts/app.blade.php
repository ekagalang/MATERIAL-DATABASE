<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $explicitTitle = trim($__env->yieldContent('title', ''));
        $topbarTitle = $explicitTitle !== '' ? $explicitTitle : 'Database Material';
        $routeTitleMap = [
            'dashboard' => 'Dashboard',
            'profile.*' => 'Profile',
            'material-calculations.*' => 'Proyek',
            'material-calculator.*' => 'Proyek',
            'materials.*' => 'Database Material',
            'bricks.*' => 'Database Material',
            'cements.*' => 'Database Material',
            'nats.*' => 'Database Material',
            'sands.*' => 'Database Material',
            'cats.*' => 'Database Material',
            'ceramics.*' => 'Database Material',
            'stores.*' => 'Database Toko',
            'work-items.*' => 'Item Pekerjaan',
            'workers.*' => 'Tenaga Kerja    ',
            'skills.*' => 'Keahlian',
            'units.*' => 'Satuan Unit',
            'settings.*' => 'Pengaturan',
        ];
        foreach ($routeTitleMap as $pattern => $title) {
            if (request()->routeIs($pattern)) {
                $topbarTitle = $title;
                break;
            }
        }
    @endphp
    <title>{{ $topbarTitle }}</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ @filemtime(public_path('css/global.css')) }}">
    <script src="{{ asset('js/number-helper-client.js') }}"></script>

    {{-- Anti-Flicker / FOUC Prevention --}}
    <style>
        /* Hide body until page is ready */
        html:not(.page-ready) body {
            opacity: 0;
            transition: opacity 0.15s ease-in;
        }

        html.page-ready body {
            opacity: 1;
        }

        /* Prevent table layout shift */
        table {
            table-layout: fixed;
        }

        .table-preview,
        .table-rekap-global {
            width: 100%;
            border-collapse: collapse;
        }

        /* Prevent form input flicker */
        input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            will-change: contents;
        }

        .topbar-account {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
        }

        .topbar-account-dropdown .dropdown-toggle::after {
            display: none;
        }

        .topbar-account-trigger {
            min-height: 44px;
            padding: 6px 8px 6px 12px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .topbar-account-trigger:hover,
        .topbar-account-trigger:focus {
            transform: translateY(-1px);
            border-color: rgba(137, 19, 19, 0.22);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }

        .topbar-account-meta {
            display: grid;
            gap: 1px;
            text-align: right;
        }

        .topbar-account-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #891313 0%, #e10009 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-role {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            white-space: nowrap;
        }

        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: linear-gradient(135deg, #891313 0%, #e10009 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(137, 19, 19, 0.18);
            flex-shrink: 0;
        }

        .topbar-chevron {
            color: #94a3b8;
            font-size: 11px;
            transition: transform .16s ease;
        }

        .topbar-account-dropdown .dropdown-toggle[aria-expanded="true"] .topbar-chevron {
            transform: rotate(180deg);
        }

        .topbar-account-menu {
            width: min(280px, calc(100vw - 32px));
            margin-top: 10px !important;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 20px;
            padding: 10px;
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.16);
        }

        .topbar-menu-head {
            padding: 10px 12px 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(255, 243, 240, 0.92) 0%, rgba(248, 250, 252, 0.95) 100%);
            border: 1px solid rgba(244, 196, 191, 0.8);
            margin-bottom: 8px;
        }

        .topbar-menu-name {
            font-size: 0.92rem;
            line-height: 1.15;
            font-weight: 800;
            color: #172033;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .topbar-menu-email {
            font-size: 0.74rem;
            color: #6b7280;
            margin: 4px 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-menu-role {
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #891313;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .topbar-account-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 12px;
            padding: 10px 12px;
            color: #172033;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .topbar-account-menu .dropdown-item:hover,
        .topbar-account-menu .dropdown-item:focus {
            background: #f8fafc;
        }

        .topbar-account-menu .dropdown-item.text-danger {
            color: #b91c1c !important;
        }

        .topbar-account-menu .dropdown-divider {
            margin: 8px 2px;
            border-color: rgba(226, 232, 240, 0.9);
        }

        .topbar-logout-button {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
        }

        .artifact-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            width: 100%;
            min-height: 160px;
        }

        .artifact-loading--compact {
            padding: 28px 18px;
            min-height: 120px;
        }

        .artifact-loading__spinner {
            width: 48px;
            height: 48px;
            position: relative;
            border-radius: 50%;
            animation: artifact-spin 3s linear infinite;
        }

        .artifact-loading--compact .artifact-loading__spinner {
            width: 38px;
            height: 38px;
        }

        .artifact-loading__spinner::before,
        .artifact-loading__spinner::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 4px solid transparent;
        }

        .artifact-loading__spinner::before {
            border-top-color: #f59e0b;
            border-right-color: rgba(245, 158, 11, 0.3);
            animation: artifact-spin-reverse 1.6s linear infinite;
        }

        .artifact-loading__spinner::after {
            inset: 6px;
            border-bottom-color: #0ea5e9;
            border-left-color: rgba(14, 165, 233, 0.28);
            animation: artifact-spin 1.15s linear infinite;
        }

        @keyframes artifact-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes artifact-spin-reverse {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }
    </style>

    {{-- Page Ready Script - Run ASAP --}}
    <script>
        // Mark page as ready after DOM and critical resources load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                requestAnimationFrame(function() {
                    document.documentElement.classList.add('page-ready');
                });
            });
        } else {
            document.documentElement.classList.add('page-ready');
        }
    </script>
</head>
<body>
    <div class="global-topbar" id="globalTopbar">
        <button type="button" class="topbar-logo-btn" id="navLogoToggle" aria-label="Buka menu">
            <img src="/kanggo.png" alt="Kanggo">
        </button>
        <div class="topbar-title"><i class="bi bi-caret-right-fill"></i> {{ $topbarTitle }} @yield('topbar-badge')</div>
        <div class="topbar-account">
            @auth
                @php
                    $activeUser = auth()->user();
                    $activeRole = $activeUser->getRoleNames()->first() ?? 'user';
                @endphp
                <div class="dropdown topbar-account-dropdown">
                    <button
                        class="btn topbar-account-trigger dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <div class="topbar-account-meta">
                            <span class="topbar-account-name">{{ $activeUser->name }}</span>
                            <span class="topbar-role">{{ \Illuminate\Support\Str::headline($activeRole) }}</span>
                        </div>
                        <span class="topbar-avatar" aria-hidden="true" title="{{ $activeUser->name }}">
                            {{ strtoupper(substr($activeUser->name, 0, 1)) }}
                        </span>
                        <i class="bi bi-chevron-down topbar-chevron"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end topbar-account-menu">
                        <div class="topbar-menu-head">
                            <p class="topbar-menu-name">{{ $activeUser->name }}</p>
                            <p class="topbar-menu-email">{{ $activeUser->email }}</p>
                            <span class="topbar-menu-role">{{ \Illuminate\Support\Str::headline($activeRole) }}</span>
                        </div>

                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person-badge"></i>
                            <span>Profile</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger topbar-logout-button">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
    <div class="nav-overlay" id="navOverlay"></div>
    <aside class="sidebar-nav" id="sidebarNav">
        <div class="nav">
            @can('dashboard.view')
                <a href="{{ url('/') }}" class="{{ request()->is('/') || request()->routeIs('material-calculator.dashboard') || request()->routeIs('material-calculations.*') ? 'active' : '' }}">
                    <i class="bi bi-houses"></i></i> Dashboard
                </a>
            @endcan

            @canany(['materials.view', 'materials.manage'])
                <!-- Material Dropdown (Modified for Return & Hover) -->
                <div class="nav-dropdown-wrapper material-wrapper">
                    <a href="{{ route('materials.index') }}" class="nav-link-btn {{ request()->routeIs('materials.*') || request()->routeIs('bricks.*') || request()->routeIs('cements.*') || request()->routeIs('nats.*') || request()->routeIs('sands.*') || request()->routeIs('cats.*') ? 'active' : '' }}" id="materialNavLink">
                        <i class="bi bi-box-seam"></i> Material <i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </a>

                    <div class="nav-dropdown-menu" id="materialDropdownMenu">
                        <div class="nav-dropdown-content">
                            <div class="dropdown-item-parent">
                                <div class="dropdown-item-trigger" tabindex="0" role="button">
                                    Lihat Material
                                    <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                                </div>

                                <div class="dropdown-sub-menu">
                                    <div class="dropdown-header">Pilih Material</div>
                                    <div class="work-type-autocomplete nav-material-autocomplete">
                                        <div class="work-type-input nav-material-input">
                                            <input type="text" id="navMaterialSearchInput" class="autocomplete-input" placeholder="Cari jenis material..." autocomplete="off" aria-label="Cari jenis material">
                                        </div>
                                        <div class="autocomplete-list" id="navMaterialSearchList"></div>
                                    </div>
                                    <div class="dropdown-grid">
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="brick"> Bata</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cat"> Cat</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="ceramic"> Keramik</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="sand"> Pasir</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cement"> Semen</label>
                                    </div>
                                    <div class="nav-material-actions">
                                        <button type="button" id="applyMaterialFilter" class="btn btn-primary-glossy btn-sm nav-material-apply">Terapkan Filter</button>
                                        <button type="button" id="resetMaterialFilterNav" class="btn btn-outline-danger btn-sm nav-material-reset">Reset</button>
                                    </div>
                                </div>
                            </div>

                            @can('materials.manage')
                                <div class="dropdown-item-parent">
                                    <div class="dropdown-item-trigger" tabindex="0" role="button">
                                        Tambah Material
                                        <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                                    </div>

                                    <div class="dropdown-sub-menu">
                                        <div class="dropdown-header">Pilih Material</div>
                                        <div class="work-type-autocomplete nav-material-autocomplete">
                                            <div class="work-type-input nav-material-input">
                                                <input type="text" id="navAddMaterialSearchInput" class="autocomplete-input" placeholder="Cari jenis untuk tambah..." autocomplete="off" aria-label="Cari jenis untuk tambah">
                                            </div>
                                            <div class="autocomplete-list" id="navAddMaterialSearchList"></div>
                                        </div>
                                        <div class="dropdown-grid">
                                            <a href="{{ route('bricks.create') }}" class="dropdown-item global-open-modal">Bata</a>
                                            <a href="{{ route('cats.create') }}" class="dropdown-item global-open-modal">Cat</a>
                                            <a href="{{ route('ceramics.create') }}" class="dropdown-item global-open-modal">Keramik</a>
                                            <a href="{{ route('sands.create') }}" class="dropdown-item global-open-modal">Pasir</a>
                                            <a href="{{ route('cements.create') }}" class="dropdown-item global-open-modal">Semen</a>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            @endcanany

            <style>
                /* Hover Logic for Navbar Dropdowns */
                .material-wrapper:hover .nav-dropdown-menu,
                .work-item-wrapper:hover .nav-dropdown-menu,
                .settings-wrapper:hover .nav-dropdown-menu {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0);
                    pointer-events: auto;
                }

                .sidebar-nav .material-wrapper:hover .nav-dropdown-menu,
                .sidebar-nav .work-item-wrapper:hover .nav-dropdown-menu,
                .sidebar-nav .settings-wrapper:hover .nav-dropdown-menu {
                    transform: translateX(0);
                }
                
                /* Ensure Link looks like button */
                #materialNavLink {
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const materialLink = document.getElementById('materialNavLink');
                    const lastUrl = localStorage.getItem('lastMaterialsUrl');

                    function isMaterialsIndexUrl(url) {
                        if (!url) return false;
                        try {
                            const baseUrl = new URL('{{ route('materials.index') }}', window.location.origin);
                            const parsedUrl = new URL(url, window.location.origin);
                            return parsedUrl.pathname === baseUrl.pathname;
                        } catch (error) {
                            return false;
                        }
                    }

                    if (materialLink && lastUrl) {
                        if (isMaterialsIndexUrl(lastUrl)) {
                            materialLink.href = lastUrl;
                        } else {
                            localStorage.removeItem('lastMaterialsUrl');
                        }
                    }

                    const calcLink = document.getElementById('calcNavLink');
                    let calcBaseHref = null;

                    function getCalcSession() {
                        const calcSessionRaw = localStorage.getItem('materialCalculationSession');
                        if (!calcSessionRaw) return null;
                        try {
                            return JSON.parse(calcSessionRaw);
                        } catch (error) {
                            return null;
                        }
                    }

                    function getCalcPreviewInfo() {
                        const raw = localStorage.getItem('materialCalculationPreview');
                        if (!raw) return { status: 'none' };
                        try {
                            const parsed = JSON.parse(raw);
                            if (!parsed || typeof parsed !== 'object') return { status: 'none' };
                            const url = parsed.url ? String(parsed.url) : '';
                            const updatedAt = Number(parsed.updatedAt || 0);
                            if (!url || !updatedAt) return { status: 'none' };
                            if (Date.now() - updatedAt > 60 * 60 * 1000) return { status: 'expired' };
                            const parsedUrl = new URL(url, window.location.origin);
                            if (!parsedUrl.pathname.includes('/material-calculations/preview/')) return { status: 'none' };
                            return { status: 'valid', url: parsedUrl.toString() };
                        } catch (error) {
                            return { status: 'none' };
                        }
                    }

                    function showCalcSessionExpiredAlert() {
                        const message = 'Session perhitungan di server sudah habis. Silakan hitung ulang untuk hasil terbaru.';
                        if (typeof window.showToast === 'function') {
                            window.showToast(message, 'error');
                        } else {
                            alert(message);
                        }
                    }

                    function buildCalcResumeHref() {
                        if (!calcBaseHref) return null;
                        const previewInfo = getCalcPreviewInfo();
                        if (previewInfo.status === 'expired') {
                            localStorage.removeItem('materialCalculationPreview');
                            showCalcSessionExpiredAlert();
                        }
                        if (previewInfo.status === 'valid') {
                            return previewInfo.url;
                        }
                        const resumeUrl = new URL(calcBaseHref, window.location.origin);
                        const calcSession = getCalcSession();
                        if (calcSession && typeof calcSession === 'object') {
                            resumeUrl.searchParams.set('resume', '1');
                            if (calcSession.autoSubmit) {
                                resumeUrl.searchParams.set('auto_submit', '1');
                            } else {
                                resumeUrl.searchParams.delete('auto_submit');
                            }
                        } else {
                            resumeUrl.searchParams.delete('resume');
                            resumeUrl.searchParams.delete('auto_submit');
                        }
                        return resumeUrl.toString();
                    }

                    if (calcLink) {
                        const baseHref = calcLink.getAttribute('href') || calcLink.href;
                        if (baseHref) {
                            const cleanUrl = new URL(baseHref, window.location.origin);
                            cleanUrl.searchParams.delete('resume');
                            cleanUrl.searchParams.delete('auto_submit');
                            calcBaseHref = cleanUrl.toString();
                            calcLink.href = calcBaseHref;
                        }
                    }

                    const workItemToggle = document.getElementById('workItemDropdownToggle');
                    if (workItemToggle && calcLink) {
                        workItemToggle.addEventListener('click', function(e) {
                            if (e.detail === 0) return;
                            if (e.target && e.target.closest('.nav-caret')) return;
                            window.location.href = buildCalcResumeHref() || calcLink.href;
                        });
                    }
                });
            </script>

            @canany(['stores.view', 'stores.manage'])
                <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                    <i class="bi bi-shop"></i> Toko
                </a>
            @endcanany

            @canany(['work-items.view', 'work-items.manage', 'calculations.view', 'calculations.manage', 'projects.view', 'projects.manage'])
                <div class="nav-dropdown-wrapper work-item-wrapper">
                    <button type="button" class="nav-link-btn {{ request()->routeIs('work-items.*') || request()->routeIs('material-calculations.*') ? 'active' : '' }}" id="workItemDropdownToggle">
                        <i class="bi bi-building-gear"></i> Proyek <i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </button>

                    <div class="nav-dropdown-menu" id="workItemDropdownMenu">
                        <div class="nav-dropdown-content">
                            @canany(['work-items.view', 'work-items.manage', 'projects.view', 'projects.manage'])
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('work-items.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Lihat Daftar Item Pekerjaan
                                    </a>
                                </div>
                            @endcanany

                            @canany(['calculations.manage', 'projects.manage'])
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('material-calculations.create') }}" id="calcNavLink"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Hitung Item Pekerjaan Proyek
                                    </a>
                                </div>

                                <div class="dropdown-item-parent">
                                    <a href="https://docs.google.com/spreadsheets/d/1tsEQ3a4duHw2AROxsbHaz41n3EiwoFQEpqmWc5XdMP4/edit?usp=sharing" target="_blank"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Tambah Item Pekerjaan
                                    </a>
                                </div>
                            @endcanany
                        </div>
                    </div>
                </div>
            @endcanany

            @can('workers.view')
                <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Tukang
                </a>
            @endcan

            @can('skills.view')
                <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                    <i class="bi bi-tools"></i> Keahlian
                </a>
            @endcan

            @canany(['units.view', 'units.manage'])
                <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">
                    <i class="bi bi-rulers"></i> Satuan
                </a>
            @endcanany

            @canany(['recommendations.manage', 'work-taxonomy.manage', 'store-search-radius.manage', 'settings.manage', 'roles.manage', 'users.manage'])
                <!-- Settings Dropdown -->
                <div class="nav-dropdown-wrapper settings-wrapper" style="margin-left: auto;">
                    <button type="button" class="nav-link-btn {{ request()->routeIs('settings.*') ? 'active' : '' }}" id="settingsDropdownToggle">
                        <i class="bi bi-gear"></i> Pengaturan<i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </button>

                    <div class="nav-dropdown-menu" id="settingsDropdownMenu" style="left: auto; right: 0;">
                        <div class="nav-dropdown-content">
                            @canany(['recommendations.manage', 'settings.manage'])
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.recommendations.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen Filter Preferensi
                                    </a>
                                </div>
                            @endcanany
                            @canany(['store-search-radius.manage', 'settings.manage'])
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.store-search-radius.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Radius Pencarian Toko
                                    </a>
                                </div>
                            @endcanany
                            @canany(['work-taxonomy.manage', 'settings.manage'])
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.work-floors.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen Lantai
                                    </a>
                                </div>
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.work-areas.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen Area
                                    </a>
                                </div>
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.work-fields.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen Bidang
                                    </a>
                                </div>
                            @endcanany
                            @can('roles.manage')
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.roles.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen Role
                                    </a>
                                </div>
                            @endcan
                            @can('users.manage')
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.users.index') }}"
                                    class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                                    role="button">
                                        Manajemen User
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            @endcanany
        </div>
    </aside>

    <div class="container page-content">

        <div id="toast-container" class="toast-container" role="status" aria-live="polite" aria-atomic="true"></div>
        <!-- Confirm Modal moved outside to prevent z-index trapping -->

        @php
            $toasts = [];
            if (session('success')) {
                $toasts[] = ['type' => 'success', 'message' => session('success')];
            }
            if (session('error')) {
                $toasts[] = ['type' => 'error', 'message' => session('error')];
            }
        @endphp
        <script>
            window.__TOASTS__ = @json($toasts);
        </script>

        @yield('content')
    </div>

    <div id="confirm-modal" class="confirm-modal" aria-hidden="true">
        <div class="confirm-backdrop" data-confirm-close></div>
        <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <div class="confirm-header">
                <div class="confirm-title" id="confirm-title">Konfirmasi</div>
                <button type="button" class="confirm-close" data-confirm-close aria-label="Tutup">&times;</button>
            </div>
            <div class="confirm-message" id="confirm-message">Apakah Anda yakin?</div>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel" id="confirm-cancel">Batal</button>
                <button type="button" class="confirm-btn confirm" id="confirm-ok">Hapus</button>
            </div>
        </div>
    </div>

    <!-- Floating Modal Global (Unique ID to avoid conflict) -->
    <div id="globalFloatingModal" class="floating-modal global-modal-layer">
        <div class="floating-modal-backdrop"></div>
        <div class="floating-modal-content">
            <div class="floating-modal-header">
                <h2 id="globalModalTitle">Detail Material</h2>
                <button class="floating-modal-close" id="globalCloseModal">&times;</button>
            </div>
            <div class="floating-modal-body" id="globalModalBody">
                @include('partials.artifact-loading', [
                    'message' => 'Memuat form...',
                    'detail' => 'Menyiapkan tampilan modal.',
                ])
            </div>
        </div>
    </div>

    @yield('modals')

    <!-- Custom styles per halaman -->
    @stack('styles')

    <!-- Global Sanitizer Script -->
    <script src="{{ asset('js/form-sanitizer.js') }}"></script>
    <script src="{{ asset('js/dynamic-dropdown-position.js') }}?v={{ @filemtime(public_path('js/dynamic-dropdown-position.js')) }}"></script>
    <script src="{{ asset('js/google-maps-picker.js') }}?v={{ @filemtime(public_path('js/google-maps-picker.js')) }}"></script>

    <!-- Custom scripts per halaman -->
    <script>
        (function() {
            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            window.getArtifactLoadingMarkup = function(options = {}) {
                const message = escapeHtml(options.message || 'Memuat data...');
                const compactClass = options.compact ? ' artifact-loading--compact' : '';

                return `
                    <div class="artifact-loading${compactClass}" role="status" aria-live="polite">
                        <div class="artifact-loading__spinner" aria-hidden="true"></div>
                        <span class="visually-hidden">${message}</span>
                    </div>
                `;
            };

            window.getArtifactLoadingTableRow = function(colspan, options = {}) {
                const safeColspan = Number(colspan) > 0 ? Number(colspan) : 1;

                return `
                    <tr>
                        <td colspan="${safeColspan}" style="padding: 0;">
                            ${window.getArtifactLoadingMarkup(options)}
                        </td>
                    </tr>
                `;
            };

            function shouldSkipLoadingUpgrade(element) {
                return !!element.closest(
                    'button, .btn, [type="submit"], .toast, .confirm-dialog, .progress, #loadingOverlay'
                );
            }

            function canReplaceContainer(container) {
                if (!container) {
                    return false;
                }

                if (container.querySelector('.artifact-loading')) {
                    return false;
                }

                if (
                    container.querySelector(
                        'form, input, select, textarea, .list-group-item, .table-container table:not(.material-skeleton-table)'
                    )
                ) {
                    return false;
                }

                const text = (container.textContent || '').trim();
                return /loading|memuat|calculating|menghitung/i.test(text);
            }

            function buildLoadingOptions(container) {
                const text = (container.textContent || '').replace(/\s+/g, ' ').trim();

                if (/form/i.test(text)) {
                    return {
                        message: 'Memuat form...',
                        detail: 'Menyiapkan tampilan editor.',
                    };
                }

                if (/calculating|menghitung/i.test(text)) {
                    return {
                        message: 'Memproses data...',
                        detail: 'Menjalankan perhitungan yang dibutuhkan.',
                    };
                }

                return {
                    message: 'Memuat data...',
                    detail: 'Mohon tunggu sebentar.',
                    compact: !!container.closest('td, .loading-placeholder'),
                };
            }

            window.upgradeLegacyLoadingStates = function(root = document) {
                const scope =
                    root instanceof Element || root instanceof DocumentFragment || root instanceof Document
                        ? root
                        : document;

                const indicatorNodes = scope.querySelectorAll(
                    '.spinner-border, .fa-spinner, .fa-spin'
                );

                indicatorNodes.forEach((indicator) => {
                    if (!(indicator instanceof Element) || shouldSkipLoadingUpgrade(indicator)) {
                        return;
                    }

                    const container =
                        indicator.closest('.floating-modal-body, .loading-placeholder, td[colspan], #traceContent') ||
                        indicator.parentElement;

                    if (!canReplaceContainer(container)) {
                        return;
                    }

                    container.innerHTML = window.getArtifactLoadingMarkup(buildLoadingOptions(container));
                });

                const textOnlyContainers = scope.querySelectorAll(
                    '.floating-modal-body, #globalModalBody, #modalBody, #traceContent'
                );

                textOnlyContainers.forEach((container) => {
                    if (!(container instanceof Element) || !canReplaceContainer(container)) {
                        return;
                    }

                    container.innerHTML = window.getArtifactLoadingMarkup(buildLoadingOptions(container));
                });
            };
        })();
    </script>

    @stack('scripts')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.upgradeLegacyLoadingStates === 'function') {
                window.upgradeLegacyLoadingStates(document);

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node instanceof Element) {
                                window.upgradeLegacyLoadingStates(node);
                                if (node.parentElement) {
                                    window.upgradeLegacyLoadingStates(node.parentElement);
                                }
                            }
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            }

            function insertAtCursor(input, text) {
                try {
                    if (typeof input.setRangeText === 'function') {
                        const start = input.selectionStart ?? input.value.length;
                        const end = input.selectionEnd ?? input.value.length;
                        input.setRangeText(text, start, end, 'end');
                        return;
                    }
                } catch (err) {
                    // Fallback below
                }
                input.value = (input.value || '') + text;
            }
            const navToggle = document.getElementById('navToggle');
            const navOverlay = document.getElementById('navOverlay');
            const navLogoToggle = document.getElementById('navLogoToggle');

            function closeNav() {
                document.body.classList.remove('nav-open');
            }

            function toggleNav() {
                document.body.classList.toggle('nav-open');
            }

            if (navToggle) {
                navToggle.addEventListener('click', toggleNav);
            }

            if (navLogoToggle) {
                navLogoToggle.addEventListener('click', toggleNav);
            }

            if (navOverlay) {
                navOverlay.addEventListener('click', closeNav);
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeNav();
                }
            });

            // --- Navbar Dropdown Logic (Reusable Function) ---
            // Track all active dropdowns globally
            const activeDropdowns = new Set();

            // Function to close all dropdowns except the specified one
            function closeAllDropdownsExcept(exceptToggleId = null) {
                activeDropdowns.forEach(dropdownInfo => {
                    if (dropdownInfo.toggleId !== exceptToggleId) {
                        dropdownInfo.closeDropdown();
                    }
                });
            }

            function initializeDropdown(toggleId, menuId) {
                const dropdownToggle = document.getElementById(toggleId);
                const dropdownMenu = document.getElementById(menuId);
                const dropdownWrapper = dropdownToggle ? dropdownToggle.closest('.nav-dropdown-wrapper') : null;

            if (dropdownToggle && dropdownMenu) {
                // Helper functions
                const openDropdown = () => {
                    // Close all other dropdowns first
                    closeAllDropdownsExcept(toggleId);

                    dropdownMenu.classList.add('show');
                    dropdownToggle.classList.add('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                };

                const closeDropdown = () => {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                };

                const scrollFocusedItemIntoView = (target) => {
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    const scrollContainer = target.closest('.dropdown-sub-menu .dropdown-grid, .nav-dropdown-content');
                    if (!(scrollContainer instanceof HTMLElement)) {
                        return;
                    }

                    requestAnimationFrame(() => {
                        target.scrollIntoView({
                            block: 'nearest',
                            inline: 'nearest',
                        });
                    });
                };

                // Register this dropdown in the global set
                activeDropdowns.add({
                    toggleId: toggleId,
                    closeDropdown: closeDropdown
                });

                // Mouse/Click Events
                dropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (dropdownMenu.classList.contains('show')) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!dropdownWrapper.contains(e.target)) {
                        closeDropdown();
                    }
                });

                // Keyboard Navigation for Toggle
                dropdownToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openDropdown();
                        const firstItem = dropdownMenu.querySelector('.dropdown-item-trigger');
                        if (firstItem) firstItem.focus();
                    } else if (e.key === 'Escape') {
                        closeDropdown();
                    }
                });

                // Parent Items Navigation
                const parentItems = dropdownMenu.querySelectorAll('.dropdown-item-parent');
                parentItems.forEach((parent, index) => {
                    const trigger = parent.querySelector('.dropdown-item-trigger');
                    if (!trigger) return;

                    trigger.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            const nextParent = parentItems[index + 1];
                            if (nextParent) {
                                const nextTrigger = nextParent.querySelector('.dropdown-item-trigger');
                                if (nextTrigger) {
                                    nextTrigger.focus();
                                    scrollFocusedItemIntoView(nextTrigger);
                                }
                            }
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const prevParent = parentItems[index - 1];
                            if (prevParent) {
                                const prevTrigger = prevParent.querySelector('.dropdown-item-trigger');
                                if (prevTrigger) {
                                    prevTrigger.focus();
                                    scrollFocusedItemIntoView(prevTrigger);
                                }
                            } else {
                                dropdownToggle.focus();
                                closeDropdown();
                            }
                        } else if (e.key === 'ArrowRight' || e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            const firstChild = parent.querySelector('.dropdown-sub-menu input, .dropdown-sub-menu a, .dropdown-sub-menu button');
                            if (firstChild) {
                                firstChild.focus();
                                scrollFocusedItemIntoView(firstChild);
                            }
                        } else if (e.key === 'Escape' || e.key === 'ArrowLeft') {
                            e.preventDefault();
                            closeDropdown();
                            dropdownToggle.focus();
                        }
                    });
                });

                // Submenu Items Navigation
                const allSubmenuItems = Array.from(dropdownMenu.querySelectorAll('.dropdown-sub-menu input, .dropdown-sub-menu a, .dropdown-sub-menu button'));
                
                allSubmenuItems.forEach((item, index) => {
                    item.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowLeft' || e.key === 'Escape') {
                            e.preventDefault();
                            const parentTrigger = item.closest('.dropdown-item-parent').querySelector('.dropdown-item-trigger');
                            if (parentTrigger) {
                                parentTrigger.focus();
                                scrollFocusedItemIntoView(parentTrigger);
                            }
                        } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                            // Navigate to next item IF it is in the same submenu
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const nextItem = allSubmenuItems[index + 1];
                            if (nextItem && nextItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                nextItem.focus();
                                scrollFocusedItemIntoView(nextItem);
                            }
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const prevItem = allSubmenuItems[index - 1];
                            if (prevItem && prevItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                prevItem.focus();
                                scrollFocusedItemIntoView(prevItem);
                            }
                        } else if (e.key === 'Enter' && item.tagName === 'INPUT' && item.type === 'checkbox') {
                             // Allow default behavior for checkboxes (toggle)
                             // No preventDefault() here
                        }
                    });
                });

                dropdownMenu.addEventListener('focusin', function(event) {
                    scrollFocusedItemIntoView(event.target);
                });
            }
            }

            // Initialize all dropdowns
            initializeDropdown('materialDropdownToggle', 'materialDropdownMenu');
            initializeDropdown('workItemDropdownToggle', 'workItemDropdownMenu');
            initializeDropdown('settingsDropdownToggle', 'settingsDropdownMenu');

            // --- Navbar Material Filter Logic (Tick & Go) ---
            const navToggles = document.querySelectorAll('.nav-material-toggle');
            const applyFilterBtn = document.getElementById('applyMaterialFilter');
            const resetFilterBtn = document.getElementById('resetMaterialFilterNav');
            const STORAGE_KEY = 'materials_index_filter_preferences';
            const materialTypeSuggestionState = {
                loaded: false,
                items: [],
                cache: {}
            };

            function normalizeMaterialType(text) {
                return (text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/gi, ' ')
                    .trim()
                    .replace(/\s+/g, ' ');
            }

            function normalizeMaterialTypeAlias(type) {
                const raw = String(type || '').trim().toLowerCase();
                if (!raw) return '';
                return raw === 'nat' ? 'cement' : raw;
            }

            function filterMaterialTypeOptions(term, options) {
                const query = normalizeMaterialType(term);
                if (!query) return options;
                return options.filter(option => {
                    const label = normalizeMaterialType(option.label);
                    return label.includes(query);
                });
            }

            function renderMaterialTypeList(listEl, items, onSelect) {
                if (!listEl) return;
                listEl.innerHTML = '';
                items.forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = option.label;
                    item.addEventListener('click', function() {
                        onSelect(option);
                    });
                    listEl.appendChild(item);
                });
                listEl.style.display = items.length ? 'block' : 'none';
            }

            function loadMaterialTypeSuggestions(term = '') {
                const query = (term || '').trim();
                if (!query && materialTypeSuggestionState.loaded) {
                    return Promise.resolve(materialTypeSuggestionState.items);
                }
                if (materialTypeSuggestionState.cache[query]) {
                    return Promise.resolve(materialTypeSuggestionState.cache[query]);
                }

                const url = new URL('{{ route("materials.type-suggestions") }}', window.location.origin);
                if (query) {
                    url.searchParams.set('q', query);
                }

                return fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.ok ? response.json() : null)
                    .then(data => {
                        const items = Array.isArray(data && data.items) ? data.items : [];
                        const mappedItems = items
                            .map(item => ({
                                materialType: normalizeMaterialTypeAlias(item.material_type),
                                type: item.type,
                                label: item.label || item.type
                            }))
                            .filter(item => item.materialType && item.type);
                        materialTypeSuggestionState.cache[query] = mappedItems;
                        if (!query) {
                            materialTypeSuggestionState.items = mappedItems;
                            materialTypeSuggestionState.loaded = true;
                        }
                        return mappedItems;
                    })
                    .catch(() => {
                        if (!query) {
                            materialTypeSuggestionState.loaded = true;
                            materialTypeSuggestionState.items = [];
                        }
                        materialTypeSuggestionState.cache[query] = [];
                        return materialTypeSuggestionState.cache[query];
                    });
            }

            const navMaterialSearchInput = document.getElementById('navMaterialSearchInput');
            const navMaterialSearchList = document.getElementById('navMaterialSearchList');
            const navAddMaterialSearchInput = document.getElementById('navAddMaterialSearchInput');
            const navAddMaterialSearchList = document.getElementById('navAddMaterialSearchList');

            function showMaterialSuggestions(term, listEl, onSelect) {
                const query = (term || '').trim();
                if (!query && materialTypeSuggestionState.loaded) {
                    renderMaterialTypeList(listEl, materialTypeSuggestionState.items, onSelect);
                    return;
                }
                loadMaterialTypeSuggestions(query).then(options => {
                    renderMaterialTypeList(listEl, options, onSelect);
                });
            }

            if (navMaterialSearchInput && navMaterialSearchList) {
                function closeNavMaterialList() {
                    navMaterialSearchList.style.display = 'none';
                }

                function buildSearchFilter(materialType) {
                    const normalizedMaterialType = normalizeMaterialTypeAlias(materialType);
                    if (!normalizedMaterialType) {
                        return { selected: [], order: [] };
                    }

                    let currentFilter = { selected: [], order: [] };
                    try {
                        const stored = localStorage.getItem(STORAGE_KEY);
                        currentFilter = stored ? JSON.parse(stored) : currentFilter;
                    } catch (e) {
                        currentFilter = { selected: [], order: [] };
                    }

                    const selected = Array.isArray(currentFilter.selected)
                        ? currentFilter.selected.map(item => normalizeMaterialTypeAlias(item)).filter(Boolean)
                        : [];
                    const order = Array.isArray(currentFilter.order)
                        ? currentFilter.order.map(item => normalizeMaterialTypeAlias(item)).filter(Boolean)
                        : [];

                    if (!selected.includes(normalizedMaterialType)) {
                        selected.push(normalizedMaterialType);
                    }

                    const nextOrder = [normalizedMaterialType, ...order.filter(item => item !== normalizedMaterialType)];
                    return { selected: selected, order: nextOrder };
                }

                function navigateToMaterialType(materialType, materialValue) {
                    const normalizedMaterialType = normalizeMaterialTypeAlias(materialType);
                    if (!normalizedMaterialType) return;
                    const updatedFilter = buildSearchFilter(normalizedMaterialType);

                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(updatedFilter));
                        localStorage.setItem('materialActiveTab', normalizedMaterialType);
                        localStorage.setItem('materialNavSearchBlink', normalizedMaterialType);
                        if (materialValue) {
                            localStorage.setItem('materialNavSearchType', materialValue);
                        } else {
                            localStorage.removeItem('materialNavSearchType');
                        }
                    } catch (e) {
                        // Ignore storage errors
                    }

                    window.location.href = '{{ route("materials.index") }}' + '?tab=' + encodeURIComponent(normalizedMaterialType);
                }

                function findExactNavMaterial(term, items) {
                    const query = normalizeMaterialType(term);
                    if (!query) return null;
                    return items.find(option => {
                        return normalizeMaterialType(option.label) === query;
                    }) || null;
                }

                function applyNavMaterialSelection(option) {
                    navMaterialSearchInput.value = option.label;
                    closeNavMaterialList();
                    navigateToMaterialType(option.materialType, option.label);
                }

                navMaterialSearchInput.addEventListener('focus', function() {
                    showMaterialSuggestions(navMaterialSearchInput.value, navMaterialSearchList, applyNavMaterialSelection);
                });

                navMaterialSearchInput.addEventListener('input', function() {
                    const term = navMaterialSearchInput.value || '';
                    showMaterialSuggestions(term, navMaterialSearchList, applyNavMaterialSelection);
                });

                navMaterialSearchInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        const term = navMaterialSearchInput.value || '';
                        loadMaterialTypeSuggestions(term).then(options => {
                            const items = options;
                            if (!items.length) return;
                            const exact = findExactNavMaterial(term, items);
                            applyNavMaterialSelection(exact || items[0]);
                        });
                    } else if (event.key === 'Escape') {
                        closeNavMaterialList();
                    }
                });

                navMaterialSearchInput.addEventListener('blur', function() {
                    setTimeout(closeNavMaterialList, 150);
                });

                document.addEventListener('click', function(event) {
                    if (event.target === navMaterialSearchInput || navMaterialSearchList.contains(event.target)) return;
                    closeNavMaterialList();
                });
            }

            if (navAddMaterialSearchInput && navAddMaterialSearchList) {
                function closeAddMaterialList() {
                    navAddMaterialSearchList.style.display = 'none';
                }

                function applyAddMaterialSelection(option) {
                    navAddMaterialSearchInput.value = option.label;
                    closeAddMaterialList();
                    const createUrlMap = {
                        brick: '{{ route("bricks.create") }}',
                        cement: '{{ route("cements.create") }}',
                        nat: '{{ route("cements.create") }}',
                        sand: '{{ route("sands.create") }}',
                        cat: '{{ route("cats.create") }}',
                        ceramic: '{{ route("ceramics.create") }}'
                    };
                    const targetUrl = createUrlMap[normalizeMaterialTypeAlias(option.materialType)];
                    if (targetUrl && typeof openGlobalMaterialModal === 'function') {
                        openGlobalMaterialModal(targetUrl);
                    }
                }

                navAddMaterialSearchInput.addEventListener('focus', function() {
                    showMaterialSuggestions(navAddMaterialSearchInput.value, navAddMaterialSearchList, applyAddMaterialSelection);
                });

                navAddMaterialSearchInput.addEventListener('input', function() {
                    const term = navAddMaterialSearchInput.value || '';
                    showMaterialSuggestions(term, navAddMaterialSearchList, applyAddMaterialSelection);
                });

                navAddMaterialSearchInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        const term = navAddMaterialSearchInput.value || '';
                        loadMaterialTypeSuggestions(term).then(options => {
                            const items = options;
                            if (!items.length) return;
                            const exact = items.find(option => normalizeMaterialType(option.label) === normalizeMaterialType(term)) || null;
                            applyAddMaterialSelection(exact || items[0]);
                        });
                    } else if (event.key === 'Escape') {
                        closeAddMaterialList();
                    }
                });

                navAddMaterialSearchInput.addEventListener('blur', function() {
                    setTimeout(closeAddMaterialList, 150);
                });

                document.addEventListener('click', function(event) {
                    if (event.target === navAddMaterialSearchInput || navAddMaterialSearchList.contains(event.target)) return;
                    closeAddMaterialList();
                });
            }

            loadMaterialTypeSuggestions('').catch(() => {});

            // 1. Load initial state (Visual Only)
            let savedFilter;
            try {
                savedFilter = JSON.parse(localStorage.getItem(STORAGE_KEY)) || { selected: [], order: [] };
            } catch (e) {
                savedFilter = { selected: [], order: [] };
            }
            savedFilter.selected = Array.isArray(savedFilter.selected)
                ? savedFilter.selected.map(item => normalizeMaterialTypeAlias(item)).filter(Boolean)
                : [];
            savedFilter.order = Array.isArray(savedFilter.order)
                ? savedFilter.order.map(item => normalizeMaterialTypeAlias(item)).filter(Boolean)
                : [];

            navToggles.forEach(toggle => {
                const materialType = toggle.dataset.material;
                if (savedFilter.selected.includes(materialType)) {
                    toggle.checked = true;
                    toggle.closest('.dropdown-item').classList.add('checked');
                }
            });

            // 2. Handle Checkbox Click (Visual Toggle Only)
            navToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    if (this.checked) {
                        this.closest('.dropdown-item').classList.add('checked');
                    } else {
                        this.closest('.dropdown-item').classList.remove('checked');
                    }
                });
            });

            // 3. Handle "Terapkan Filter" Click (Save & Redirect)
            if (applyFilterBtn) {
                applyFilterBtn.addEventListener('click', function() {
                    const selectedMaterials = [];
                    // Preserve existing order logic if needed, or just append new ones
                    // For simplicity and robustness, we rebuild the list based on current checks
                    // but we might want to respect previous order.
                    
                    // Let's rely on a simple logic: Just save what is checked.
                    navToggles.forEach(toggle => {
                        if (toggle.checked) {
                            selectedMaterials.push(normalizeMaterialTypeAlias(toggle.dataset.material));
                        }
                    });

                    const newFilter = {
                        selected: selectedMaterials,
                        order: selectedMaterials // Simple order for now
                    };

                    localStorage.setItem(STORAGE_KEY, JSON.stringify(newFilter));

                    // Redirect logic
                    window.location.href = '{{ route("materials.index") }}';
                });
            }

            if (resetFilterBtn) {
                resetFilterBtn.addEventListener('click', function() {
                    navToggles.forEach(toggle => {
                        toggle.checked = false;
                        toggle.closest('.dropdown-item').classList.remove('checked');
                    });
                    try {
                        localStorage.removeItem(STORAGE_KEY);
                    } catch (e) {
                        // Ignore storage errors
                    }
                    if (navMaterialSearchInput) {
                        navMaterialSearchInput.value = '';
                    }
                    if (navMaterialSearchList) {
                        navMaterialSearchList.innerHTML = '';
                        navMaterialSearchList.style.display = 'none';
                    }
                });
            }


            // --- Global Modal Logic (Unique Scope) ---
            const globalModal = document.getElementById('globalFloatingModal');
            const globalModalBody = document.getElementById('globalModalBody');
            const globalModalTitle = document.getElementById('globalModalTitle');
            const globalCloseBtn = document.getElementById('globalCloseModal');
            const globalBackdrop = globalModal ? globalModal.querySelector('.floating-modal-backdrop') : null;
            let isGlobalFormDirty = false;

            function interceptGlobalFormSubmit() {
                if (!globalModalBody) {
                    console.error('[Global Modal] globalModalBody not found');
                    return;
                }
                const form = globalModalBody.querySelector('form');
                if (form) {
                    console.log('[Global Modal] Form found:', form.id, 'Action:', form.action);

                    // Add hidden input to redirect back to the current page after submit
                    let redirectInput = form.querySelector('input[name="_redirect_url"]');
                    if (!redirectInput) {
                        redirectInput = document.createElement('input');
                        redirectInput.type = 'hidden';
                        redirectInput.name = '_redirect_url';
                        form.appendChild(redirectInput);
                    }
                    // Always update the redirect URL to materials index page for sidebar actions
                    // This ensures we get the highlighting effect and see the new data
                    redirectInput.value = '{{ route("materials.index") }}';
                    console.log('[Global Modal] _redirect_url set to:', redirectInput.value);

                    // Also add _redirect_to_materials as backup
                    let redirectMaterialsInput = form.querySelector('input[name="_redirect_to_materials"]');
                    if (!redirectMaterialsInput) {
                        redirectMaterialsInput = document.createElement('input');
                        redirectMaterialsInput.type = 'hidden';
                        redirectMaterialsInput.name = '_redirect_to_materials';
                        redirectMaterialsInput.value = '1';
                        form.appendChild(redirectMaterialsInput);
                    }

                    // Prevent duplicate event listeners
                    if (!form.__submitIntercepted) {
                        form.__submitIntercepted = true;

                        // Track dirty state
                        form.addEventListener('input', () => { isGlobalFormDirty = true; });
                        form.addEventListener('change', () => { isGlobalFormDirty = true; });

                        form.addEventListener('submit', function(e) {
                            console.log('[Global Modal] Form submitting to:', form.action);
                            
                            // Show loading state before submit
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';
                            }
                            // Let form submit normally
                        });
                    }
                } else {
                    console.error('[Global Modal] No form found in modalBody');
                }
            }

            function getGlobalMaterialInfo(url) {
                let materialType = '';
                let action = '';
                let materialLabel = 'Material';

                if (url.includes('/bricks/')) { materialType = 'brick'; materialLabel = 'Bata'; } 
                else if (url.includes('/cats/')) { materialType = 'cat'; materialLabel = 'Cat'; } 
                else if (url.includes('/cements/')) { materialType = 'cement'; materialLabel = 'Semen'; } 
                else if (url.includes('/nats/')) { materialType = 'cement'; materialLabel = 'Semen'; }
                else if (url.includes('/sands/')) { materialType = 'sand'; materialLabel = 'Pasir'; }
                else if (url.includes('/ceramics/')) { materialType = 'ceramic'; materialLabel = 'Keramik'; }
                else if (url.includes('/store-locations/') || (url.includes('/stores/') && url.includes('/locations'))) { materialType = 'store-location'; materialLabel = 'Lokasi Toko'; }
                else if (url.includes('/stores/')) { materialType = 'store'; materialLabel = 'Toko'; }
                else if (url.includes('/settings/recommendations')) { materialType = 'recommendations'; materialLabel = 'Setting Rekomendasi'; }
                else if (url.includes('/settings/store-search-radius')) { materialType = 'settings'; materialLabel = 'Radius Pencarian Toko'; }

                if (url.includes('/create')) action = 'create';
                else if (url.includes('/edit')) action = 'edit';
                else if (url.includes('/show')) action = 'show';

                return { materialType, action, materialLabel };
            }

            function loadGlobalMaterialFormScript(materialType, modalBodyEl) {
                // Convert kebab-case (store-location) to camelCase (storeLocation) for variable names
                const camelType = materialType.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
                const scriptProperty = `global${camelType.charAt(0).toUpperCase() + camelType.slice(1)}FormScriptLoaded`;
                const initFunctionName = `init${camelType.charAt(0).toUpperCase() + camelType.slice(1)}Form`;

                console.log('[Script] Loading script for:', materialType);
                console.log('[Script] Init function name:', initFunctionName);
                console.log('[Script] Script already loaded?', window[scriptProperty]);

                if (!window[scriptProperty]) {
                    const script = document.createElement('script');
                    // Script file remains kebab-case (e.g., store-location-form.js)
                    script.src = `{{ asset('js') }}/${materialType}-form.js?v=${Date.now()}`;
                    console.log('[Script] Creating script element for:', script.src);

                    script.onload = () => {
                        console.log('[Script] Script loaded successfully:', script.src);
                        window[scriptProperty] = true;
                        initializeForm(initFunctionName, modalBodyEl);
                    };
                    script.onerror = () => {
                        console.error('[Script] Failed to load script:', script.src);
                        globalModalBody.innerHTML = `<div class="p-4 text-center text-danger">Gagal memuat script form: ${script.src}</div>`;
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('[Script] Script already loaded, calling init directly');
                    initializeForm(initFunctionName, modalBodyEl);
                }
            }

            let pendingGlobalTypePrefill = null;

            function applyGlobalTypePrefill(modalBodyEl) {
                if (!pendingGlobalTypePrefill || !modalBodyEl) return;
                const typeInput = modalBodyEl.querySelector('input[name="type"], input#type');
                if (typeInput) {
                    typeInput.value = pendingGlobalTypePrefill;
                    typeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    typeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                pendingGlobalTypePrefill = null;
            }

            // Helper function to load store autocomplete for global modal
            function loadGlobalStoreAutocomplete(modalBodyEl) {
                if (!window.storeAutocompleteLoaded) {
                    const storeScript = document.createElement('script');
                    storeScript.src = '{{ asset("js/store-autocomplete.js") }}?v=' + Date.now();
                    storeScript.onload = () => {
                        window.storeAutocompleteLoaded = true;
                        if (typeof initStoreAutocomplete === 'function') {
                            initStoreAutocomplete(modalBodyEl);
                        }
                    };
                    document.head.appendChild(storeScript);
                } else {
                    if (typeof initStoreAutocomplete === 'function') {
                        initStoreAutocomplete(modalBodyEl);
                    }
                }
            }

            function initializeForm(initFunctionName, modalBodyEl) {
                console.log('[Init] Initializing form with function:', initFunctionName);

                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        console.log('[Init] Function exists, calling it...');
                        // Pass pendingGlobalTypePrefill as the second argument
                        window[initFunctionName](modalBodyEl, pendingGlobalTypePrefill);
                    } else {
                        console.error('[Init] Function not found:', initFunctionName);
                    }
                    applyGlobalTypePrefill(modalBodyEl);
                    loadGlobalStoreAutocomplete(modalBodyEl);
                    interceptGlobalFormSubmit();
                }, 150); // Increased timeout slightly for safety
            }

            function ensureModalValidationStyle() {
                if (document.getElementById('modal-validation-style')) return;
                const style = document.createElement('style');
                style.id = 'modal-validation-style';
                style.textContent = `
                    .modal-validation-alert {
                        margin-bottom: 16px;
                        padding: 12px 14px;
                        border-radius: 10px;
                        border: 1px solid #fecaca;
                        background: #fef2f2;
                        color: #991b1b;
                        font-size: 13px;
                        line-height: 1.5;
                    }
                    .modal-input-invalid {
                        border-color: #ef4444 !important;
                        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
                    }
                `;
                document.head.appendChild(style);
            }

            function ensureStoreLocationModalStyle() {
                if (document.getElementById('store-location-modal-style')) return;
                const style = document.createElement('style');
                style.id = 'store-location-modal-style';
                style.textContent = `
                    #globalModalBody form.store-location-form .row {
                        gap: 0 !important;
                        --bs-gutter-x: 0 !important;
                        --bs-gutter-y: 0 !important;
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        margin-bottom: 10px !important;
                    }
                    #globalModalBody form.store-location-form .row > * {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }
                    #globalModalBody form.store-location-form .row > label {
                        width: 118px !important;
                        min-width: 118px !important;
                        flex: 0 0 118px !important;
                        margin-right: 0 !important;
                        padding-top: 6px !important;
                    }
                    @media (max-width: 992px) {
                        #globalModalBody form.store-location-form .store-location-form-grid {
                            grid-template-columns: 1fr !important;
                            gap: 20px !important;
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            function renderModalValidationErrors(form, errors) {
                ensureModalValidationStyle();

                const oldAlert = form.querySelector('.modal-validation-alert');
                if (oldAlert) oldAlert.remove();
                form.querySelectorAll('.modal-input-invalid').forEach(el => el.classList.remove('modal-input-invalid'));

                const entries = Object.entries(errors || {});
                const messages = entries.flatMap(([, value]) => Array.isArray(value) ? value : [value]).filter(Boolean);
                if (!messages.length) return;

                const alert = document.createElement('div');
                alert.className = 'modal-validation-alert';
                alert.innerHTML = `<strong>Perhatian:</strong><br>${messages.map(m => `- ${m}`).join('<br>')}`;
                form.prepend(alert);

                entries.forEach(([field]) => {
                    if (field === 'duplicate') return;
                    const escaped = field.replace(/"/g, '\\"');
                    const input = form.querySelector(`[name="${escaped}"], [name="${escaped}[]"]`);
                    if (input) {
                        input.classList.add('modal-input-invalid');
                    }
                });

                if (!form.__modalValidationCleanerBound) {
                    form.__modalValidationCleanerBound = true;
                    form.addEventListener('input', function(evt) {
                        const target = evt.target;
                        if (target && target.classList && target.classList.contains('modal-input-invalid')) {
                            target.classList.remove('modal-input-invalid');
                        }
                    });
                }
            }

            function setModalSubmitLoading(form, loading) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) return;

                if (loading) {
                    if (!submitBtn.dataset.originalHtml) {
                        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                    }
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';
                    return;
                }

                submitBtn.disabled = false;
                if (submitBtn.dataset.originalHtml) {
                    submitBtn.innerHTML = submitBtn.dataset.originalHtml;
                    delete submitBtn.dataset.originalHtml;
                }
            }

            async function submitModalFormViaAjax(form) {
                if (form.__modalSubmittingAjax) return;
                form.__modalSubmittingAjax = true;
                setModalSubmitLoading(form, true);

                try {
                    if (typeof form.__beforeModalAjaxSubmit === 'function') {
                        await Promise.resolve(form.__beforeModalAjaxSubmit());
                    }

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form),
                        credentials: 'same-origin'
                    });

                    const contentType = (response.headers.get('content-type') || '').toLowerCase();
                    const isJsonResponse = contentType.includes('application/json');
                    const payload = isJsonResponse ? await response.json().catch(() => ({})) : null;

                    if (response.status === 422) {
                        const errorPayload = payload || {};
                        const errors = errorPayload && errorPayload.errors ? errorPayload.errors : {};
                        renderModalValidationErrors(form, errors);

                        const firstMessage = Object.values(errors).flat()[0] || errorPayload.message;
                        if (firstMessage && typeof window.showToast === 'function') {
                            window.showToast(firstMessage, 'error');
                        }
                        return;
                    }

                    if (!response.ok && isJsonResponse) {
                        if (payload && typeof payload.message === 'string' && typeof window.showToast === 'function') {
                            window.showToast(payload.message, 'error');
                        }

                        throw new Error(payload && payload.message ? payload.message : 'Gagal menyimpan data.');
                    }

                    if (!response.ok) {
                        throw new Error('Gagal menyimpan data.');
                    }

                    if (isJsonResponse) {
                        const errors = payload && payload.errors ? payload.errors : {};
                        const focusMaterial = payload.new_material || payload.updated_material || null;
                        let redirectUrl = payload.redirect_url || null;
                        if (focusMaterial && focusMaterial.type && focusMaterial.id) {
                            try {
                                sessionStorage.setItem('pendingMaterialFocus', JSON.stringify(focusMaterial));
                            } catch (e) {
                                // Ignore storage errors
                            }

                            if (redirectUrl) {
                                try {
                                    const focusUrl = new URL(redirectUrl, window.location.origin);
                                    focusUrl.searchParams.set('tab', String(focusMaterial.type));
                                    focusUrl.searchParams.set('_focus_type', String(focusMaterial.type));
                                    focusUrl.searchParams.set('_focus_id', String(focusMaterial.id));
                                    redirectUrl = focusUrl.toString();
                                } catch (e) {
                                    // Keep original redirect URL if parsing fails
                                }
                            }
                        }

                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                            return;
                        }

                        if (payload.success) {
                            window.location.reload();
                            return;
                        }
                    }

                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }

                    if (response.ok) {
                        window.location.reload();
                        return;
                    }

                    throw new Error('Gagal menyimpan data.');
                } catch (error) {
                    console.error('[Modal] AJAX submit error:', error);
                    if (typeof window.showToast === 'function') {
                        window.showToast('Gagal menyimpan data. Silakan coba lagi.', 'error');
                    }
                } finally {
                    setModalSubmitLoading(form, false);
                    form.__modalSubmittingAjax = false;
                }
            }

            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (!form.closest('.floating-modal.active')) return;
                if (form.dataset.disableAjaxModalSubmit === '1') return;
                const htmlMethod = (form.getAttribute('method') || 'POST').toUpperCase();
                if (htmlMethod === 'GET') return;

                e.preventDefault();
                e.stopImmediatePropagation();

                const methodInput = form.querySelector('input[name="_method"]');
                const method = (methodInput?.value || form.method || 'POST').toUpperCase();
                const requiresConfirm = method === 'PUT' || method === 'PATCH';

                if (requiresConfirm && typeof window.showConfirm === 'function') {
                    window.showConfirm({
                        title: 'Simpan Perubahan?',
                        message: 'Apakah Anda yakin ingin menyimpan perubahan data ini?',
                        confirmText: 'Simpan',
                        cancelText: 'Batal',
                        type: 'primary'
                    }).then(confirmed => {
                        if (confirmed) {
                            submitModalFormViaAjax(form);
                        }
                    });
                    return;
                }

                submitModalFormViaAjax(form);
            }, true);

            async function closeGlobalModal() {
                if (isGlobalFormDirty) {
                    const confirmed = await window.showConfirm({
                        title: 'Batalkan Perubahan?',
                        message: 'Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?',
                        confirmText: 'Ya, Tutup',
                        cancelText: 'Kembali',
                        type: 'warning'
                    });
                    if (!confirmed) return;
                }

                if(!globalModal) return;
                globalModal.classList.remove('active');
                document.body.style.overflow = '';
                document.body.classList.remove('global-modal-open');
                setTimeout(() => {
                    globalModalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">?</div><div style="font-weight: 500;">Loading...</div></div>';
                    isGlobalFormDirty = false;
                }, 300);
            }

            // Expose close handler for form cancel buttons (prefers local modal if open)
            window.closeFloatingModal = function() {
                const localModal = document.getElementById('floatingModal');
                if (localModal && localModal.classList.contains('active') && typeof window.closeFloatingModalLocal === 'function') {
                    window.closeFloatingModalLocal();
                    return;
                }
                closeGlobalModal();
            };

            window.openGlobalMaterialModal = function(url, prefillType = null) {
                if (!globalModal || !globalModalBody || !globalModalTitle || !globalCloseBtn || !globalBackdrop) return;

                // Ensure global modal is always rendered on top-most layer.
                if (globalModal.parentElement !== document.body) {
                    document.body.appendChild(globalModal);
                } else {
                    // Re-append to keep it as the latest body child (safest stacking fallback).
                    document.body.appendChild(globalModal);
                }
                globalModal.style.setProperty('z-index', '2147483000', 'important');
                globalBackdrop.style.setProperty('z-index', '2147483001', 'important');
                const globalModalContent = globalModal.querySelector('.floating-modal-content');
                if (globalModalContent) {
                    globalModalContent.style.setProperty('z-index', '2147483002', 'important');
                }

                const { materialType, action, materialLabel } = getGlobalMaterialInfo(url);
                pendingGlobalTypePrefill = prefillType || null;
                isGlobalFormDirty = false;

                globalModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                document.body.classList.add('global-modal-open');

                // Close open sidebar dropdown states to prevent overlap with modal.
                document.querySelectorAll('.nav-dropdown-menu.show').forEach((menu) => menu.classList.remove('show'));
                document.querySelectorAll('.nav-link-btn.dropdown-open').forEach((btn) =>
                    btn.classList.remove('dropdown-open'),
                );

                if (action === 'create') {
                    globalModalTitle.textContent = `Tambah ${materialLabel} Baru`;
                    globalCloseBtn.style.display = 'flex'; 
                } else if (action === 'edit') {
                    globalModalTitle.textContent = `Edit ${materialLabel}`;
                    globalCloseBtn.style.display = 'flex'; 
                } else {
                    globalModalTitle.textContent = materialLabel;
                    globalCloseBtn.style.display = 'flex';
                }

                console.log('[Modal] Opening URL:', url);
                console.log('[Modal] Material Info:', { materialType, action, materialLabel });

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => {
                        console.log('[Modal] Response status:', response.status);
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        console.log('[Modal] Response received, parsing HTML...');
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Strategy: Find the main content container first
                        // In layouts.app, content is usually in .container
                        // We look for the main .card (which usually wraps forms) or the specific form

                        let contentElement = null;

                        // Priority 0: Special wrapper for recommendations
                        contentElement = doc.querySelector('#recommendations-content-wrapper');
                        if (contentElement) console.log('[Modal] Found content via #recommendations-content-wrapper');

                        // Priority 1: A form inside a card (standard create/edit view)
                        if (!contentElement) {
                            contentElement = doc.querySelector('.container .card form');
                            if (contentElement) console.log('[Modal] Found content via .container .card form');
                        }

                        // Priority 2: Just the card itself
                        if (!contentElement) {
                            contentElement = doc.querySelector('.container .card');
                            if (contentElement) console.log('[Modal] Found content via .container .card');
                        }

                        // Priority 3: A form directly in container
                        if (!contentElement) {
                            contentElement = doc.querySelector('.container form');
                            if (contentElement) console.log('[Modal] Found content via .container form');
                        }

                        // Priority 4: Fallback to any form (risky, but better than nothing)
                        if (!contentElement) {
                            contentElement = doc.querySelector('form');
                            if (contentElement) console.log('[Modal] Found content via form');
                        }

                        if (contentElement) {
                            console.log('[Modal] Content element found, inserting into modal...');
                            
                            // Direct insertion (matching materials.index behavior)
                            // We don't wrap in card to avoid double styling
                            globalModalBody.innerHTML = contentElement.outerHTML;

                            if (materialType === 'store-location' || globalModalBody.querySelector('form.store-location-form')) {
                                ensureStoreLocationModalStyle();
                            }

                            console.log('[Modal] Content inserted, loading scripts...');
                            if (materialType && (action === 'create' || action === 'edit' || materialType === 'recommendations')) {
                                console.log('[Modal] Loading material form script for:', materialType);
                                loadGlobalMaterialFormScript(materialType, globalModalBody);
                            } else {
                                console.log('[Modal] Intercepting form submit (no specific material type)');
                                applyGlobalTypePrefill(globalModalBody);
                                interceptGlobalFormSubmit();
                            }
                        } else {
                            throw new Error('Could not find form content in response');
                        }
                    })
                    .catch(err => {
                        globalModalBody.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #ef4444;">
                                <i class="bi bi-exclamation-triangle" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                <div style="font-weight: 600;">Gagal memuat form</div>
                                <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">${err.message}</div>
                            </div>`;
                        console.error('[Modal] Error:', err);
                    });
            }

            // Robust Global Modal Link Listener
            document.addEventListener('click', function(e) {
                const link = e.target.closest('.global-open-modal');
                if (link) {
                    e.preventDefault(); // Stop navigation immediately
                    console.log('[Global Modal] Intercepted click for:', link.href);
                    
                    if (typeof window.openGlobalMaterialModal === 'function') {
                        window.openGlobalMaterialModal(link.href);
                    } else {
                        console.error('[Global Modal] openGlobalMaterialModal function not found');
                        window.location.href = link.href; // Fallback
                    }
                }
            });

            if (globalModal && globalModalBody && globalModalTitle && globalCloseBtn && globalBackdrop) {
                globalCloseBtn.addEventListener('click', closeGlobalModal);
                globalBackdrop.addEventListener('click', closeGlobalModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && globalModal.classList.contains('active')) {
                        closeGlobalModal();
                    }
                });
            } else {
                console.warn('[Global Modal] Some elements missing, modal might not work fully.', {
                    modal: !!globalModal,
                    body: !!globalModalBody,
                    title: !!globalModalTitle,
                    close: !!globalCloseBtn,
                    backdrop: !!globalBackdrop
                });
            }
        });

        // Global Validation for Dimension and Weight Inputs
        document.addEventListener('DOMContentLoaded', function() {
            function isExpressionEnabledField(target) {
                if (!(target instanceof HTMLInputElement)) return false;
                const flag = String(target.getAttribute('data-allow-expression') || '').trim().toLowerCase();
                return flag === '1' || flag === 'true';
            }

            // Event delegation to handle both static and dynamic (modal) forms
            document.body.addEventListener('keydown', function(e) {
                const target = e.target;
                
                // Only targeting input elements
                if (target.tagName !== 'INPUT') return;
                if (isExpressionEnabledField(target)) return;

                // Identify target fields: type="number" OR fields with specific keywords in ID/Name
                // Keywords: dimension, weight, berat, panjang, lebar, tinggi, volume, price, harga
                const isNumericField = target.type === 'number' ||
                                       target.inputMode === 'numeric' ||
                                       target.inputMode === 'decimal' ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count|sides/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count|sides/i.test(target.name || '');

                if (isNumericField) {
                    // Allow: Backspace, Delete, Tab, Escape, Enter
                    if ([46, 8, 9, 27, 13].includes(e.keyCode) ||
                        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.ctrlKey === true && [65, 67, 86, 88].includes(e.keyCode)) ||
                        // Allow: home, end, left, right
                        (e.keyCode >= 35 && e.keyCode <= 39)) {
                        return;
                    }

                    // Prevent 'e', 'E', '+', '-' specifically for number inputs as they are valid in HTML5 but often unwanted
                    if (['e', 'E', '+', '-'].includes(e.key)) {
                        e.preventDefault();
                        return;
                    }

                    // Handle Decimal Point (Allow only one)
                    // 190 = Period (.), 110 = Decimal Point (numpad), 188 = Comma (,)
                    const isCommaKey = e.key === ',';
                    const isDotKey = e.key === '.';
                    const isNumpadDecimal = e.code === 'NumpadDecimal';
                    const isDecimalKey = isCommaKey || isDotKey || isNumpadDecimal || [190, 110, 188].includes(e.keyCode);

                    if (isDecimalKey) {
                        // If user types comma in number input, convert to dot
                        if (isCommaKey && target.type === 'number') {
                            e.preventDefault();
                            insertAtCursor(target, '.');
                        }
                        return; // Allow decimal separator
                    }

                    // Ensure that it is a number (0-9)
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                }
            });

            // Sanitize paste events to remove non-numeric characters
            document.body.addEventListener('paste', function(e) {
                if (e.defaultPrevented) return;
                const target = e.target;
                if (target.tagName !== 'INPUT') return;
                if (isExpressionEnabledField(target)) return;

                const isNumericField = target.type === 'number' ||
                                       target.inputMode === 'numeric' ||
                                       target.inputMode === 'decimal' ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count|sides/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count|sides/i.test(target.name || '');

                if (isNumericField) {
                    // Get pasted data via clipboard API
                    let clipboardData = (e.clipboardData || window.clipboardData).getData('text');
                    
                    // Allow numbers, one dot, one comma
                    // Clean content: Remove everything that is NOT 0-9, . or ,
                    // Note: This simplistic regex might allow "1.2.3", logic below handles strictness better but for paste simple clean is usually enough
                    if (!/^[0-9.,]+$/.test(clipboardData)) {
                        e.preventDefault();
                        // Optional: Insert cleaned data manually? 
                        // For now, blocking invalid paste is safer.
                    }
                }
            });
        });
    </script>

    <script>
        (function() {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const titles = {
                success: 'Sukses',
                error: 'Gagal',
                info: 'Info',
                warning: 'Peringatan'
            };

            function createToast(message, type = 'success', options = {}) {
                if (!message) return;
                const duration = Number(options.duration) || 4200;
                const title = options.title || titles[type] || 'Notifikasi';

                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.dataset.type = type;
                toast.style.setProperty('--toast-duration', `${duration}ms`);

                const icon = document.createElement('span');
                icon.className = 'toast-icon';
                icon.setAttribute('aria-hidden', 'true');

                const content = document.createElement('div');
                content.className = 'toast-content';

                const titleEl = document.createElement('div');
                titleEl.className = 'toast-title';
                titleEl.textContent = title;

                const messageEl = document.createElement('div');
                messageEl.className = 'toast-message';
                messageEl.textContent = message;

                content.appendChild(titleEl);
                content.appendChild(messageEl);

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'toast-close';
                close.setAttribute('aria-label', 'Tutup');
                close.textContent = '';

                const progress = document.createElement('div');
                progress.className = 'toast-progress';

                toast.appendChild(icon);
                toast.appendChild(content);
                toast.appendChild(close);
                toast.appendChild(progress);
                container.appendChild(toast);

                requestAnimationFrame(() => toast.classList.add('show'));

                let removed = false;
                const removeToast = () => {
                    if (removed) return;
                    removed = true;
                    toast.classList.add('hide');
                    window.setTimeout(() => {
                        toast.remove();
                    }, 250);
                };

                const timeoutId = window.setTimeout(removeToast, duration);

                close.addEventListener('click', () => {
                    window.clearTimeout(timeoutId);
                    removeToast();
                });
            }

            window.showToast = function(message, type = 'success', options = {}) {
                createToast(message, type, options);
            };

            const initialToasts = Array.isArray(window.__TOASTS__) ? window.__TOASTS__ : [];
            initialToasts.forEach((toast) => {
                if (toast && toast.message) {
                    createToast(toast.message, toast.type || 'success');
                }
            });

            const pending = sessionStorage.getItem('pendingToast');
            if (pending) {
                try {
                    const parsed = JSON.parse(pending);
                    if (parsed && parsed.message) {
                        createToast(parsed.message, parsed.type || 'success', parsed.options || {});
                    }
                } catch (e) {
                    console.error('Failed to parse pending toast', e);
                }
                sessionStorage.removeItem('pendingToast');
            }
        })();
    </script>

    <script>
        (function() {
            const modal = document.getElementById('confirm-modal');
            if (!modal) return;

            const titleEl = modal.querySelector('#confirm-title');
            const messageEl = modal.querySelector('#confirm-message');
            const okBtn = modal.querySelector('#confirm-ok');
            const cancelBtn = modal.querySelector('#confirm-cancel');
            const closeTargets = modal.querySelectorAll('[data-confirm-close]');

            let resolver = null;

            function closeConfirm(result) {
                if (!resolver) return;
                const resolve = resolver;
                resolver = null;
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('confirm-open');
                resolve(result);
            }

            function openConfirm(options) {
                const opts = options || {};
                titleEl.textContent = opts.title || 'Konfirmasi';
                messageEl.textContent = opts.message || 'Apakah Anda yakin?';
                okBtn.textContent = opts.confirmText || 'Hapus';
                cancelBtn.textContent = opts.cancelText || 'Batal';
                modal.dataset.type = opts.type || 'danger';

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('confirm-open');
            }

            window.showConfirm = function(options) {
                return new Promise((resolve) => {
                    if (resolver) {
                        resolver(false);
                    }
                    resolver = resolve;
                    openConfirm(options);
                });
            };

            okBtn.addEventListener('click', () => closeConfirm(true));
            cancelBtn.addEventListener('click', () => closeConfirm(false));
            closeTargets.forEach((el) => el.addEventListener('click', () => closeConfirm(false)));

            document.addEventListener('keydown', (e) => {
                if (!modal.classList.contains('active')) return;
                if (e.key === 'Escape') {
                    closeConfirm(false);
                }
            });

            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                const message = form.getAttribute('data-confirm');
                if (!message) return;
                e.preventDefault();
                const confirmed = await window.showConfirm({
                    title: form.dataset.confirmTitle || 'Konfirmasi',
                    message,
                    confirmText: form.dataset.confirmOk || 'Hapus',
                    cancelText: form.dataset.confirmCancel || 'Batal',
                    type: form.dataset.confirmType || 'danger'
                });
                if (confirmed) {
                    form.submit();
                }
            });
        })();
    </script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Performance Optimization Scripts -->
    <script src="{{ asset('js/search-debounce.js') }}"></script>
    <script src="{{ asset('js/lazy-loading.js') }}"></script>

    <!-- Skip History Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (document.body.classList.contains('skip-history')) {
                // Intercept links to replace history
                document.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        const href = link.getAttribute('href');
                        // Ignore internal anchors, JS links, or links without href
                        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                        // Ignore open in new tab
                        if (link.target === '_blank') return;

                        e.preventDefault();
                        window.location.replace(href);
                    });
                });

                // Intercept forms to fix back button behavior
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', () => {
                        // When submitting a form that leads to a new page,
                        // we want the 'Back' button on the destination page to skip THIS page.
                        // We replace the current history entry (this page) with the PREVIOUS page's URL.
                        if(document.referrer) {
                            history.replaceState(null, '', document.referrer);
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
