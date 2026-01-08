<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Database Material')</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: League Spartan -->
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=League+Spartan:wght@100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
</head>
<body>
    <button type="button" id="navToggle" class="nav-toggle-btn" aria-label="Buka menu">
        <i class="bi bi-caret-right-fill"></i>
    </button>
    <div class="nav-overlay" id="navOverlay"></div>
    <aside class="sidebar-nav" id="sidebarNav">
        <div class="nav">
            <a href="{{ url('/') }}" class="{{ request()->is('/') || request()->routeIs('material-calculator.dashboard') || request()->routeIs('material-calculations.*') ? 'active' : '' }}">
                Dashboard
            </a>
            
            <!-- Material Dropdown (Modified for Return & Hover) -->
            <div class="nav-dropdown-wrapper material-wrapper">
                <a href="{{ route('materials.index') }}" class="nav-link-btn {{ request()->routeIs('materials.*') || request()->routeIs('bricks.*') || request()->routeIs('cements.*') || request()->routeIs('sands.*') || request()->routeIs('cats.*') ? 'active' : '' }}" id="materialNavLink">
                    Material <i class="bi bi-caret-right-fill" style="font-size: 10px; opacity: 0.7;"></i>
                </a>
                
                <div class="nav-dropdown-menu" id="materialDropdownMenu">
                    <div class="nav-dropdown-content">
                        <!-- Menu Item: Lihat / Filter (Secondary) -->
                        <div class="dropdown-item-parent">
                            <div class="dropdown-item-trigger" tabindex="0" role="button">
                                Lihat Material
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </div>
                            
                            <!-- Nested Sub-Menu: Filter -->
                            <div class="dropdown-sub-menu">
                                <div class="dropdown-header">Pilih Material</div>
                                <div class="dropdown-grid">
                                    <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="brick"> Bata</label>
                                    <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cement"> Semen</label>
                                    <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="sand"> Pasir</label>
                                    <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cat"> Cat</label>
                                    <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="ceramic"> Keramik</label>
                                </div>
                                <div style="padding: 12px 16px; border-top: 1px solid #e2e8f0;">
                                    <button type="button" id="applyMaterialFilter" class="btn btn-primary-glossy  btn-sm" style="width: 100%; justify-content: center;">Terapkan Filter</button>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Item: Tambah Data (Prioritized) -->
                        <div class="dropdown-item-parent">
                            <div class="dropdown-item-trigger" tabindex="0" role="button">
                                Tambah Material
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </div>

                            <!-- Nested Sub-Menu: Add Buttons -->
                            <div class="dropdown-sub-menu">
                                <div class="dropdown-header">Pilih Material</div>
                                <div class="dropdown-grid">
                                    <a href="{{ route('bricks.create') }}" class="dropdown-item global-open-modal">Bata</a>
                                    <a href="{{ route('cements.create') }}" class="dropdown-item global-open-modal">Semen</a>
                                    <a href="{{ route('sands.create') }}" class="dropdown-item global-open-modal">Pasir</a>
                                    <a href="{{ route('cats.create') }}" class="dropdown-item global-open-modal">Cat</a>
                                    <a href="{{ route('ceramics.create') }}" class="dropdown-item global-open-modal">Keramik</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                    
                    if (materialLink && lastUrl) {
                        materialLink.href = lastUrl;
                    }
                });
            </script>

            <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                Toko
            </a>

            <!-- Item Pekerjaan Dropdown -->
            <div class="nav-dropdown-wrapper work-item-wrapper">
                <button type="button" class="nav-link-btn {{ request()->routeIs('work-items.*') ? 'active' : '' }}" id="workItemDropdownToggle">
                    Item Pekerjaan <i class="bi bi-caret-right-fill" style="font-size: 10px; opacity: 0.7;"></i>
                </button>

                <div class="nav-dropdown-menu" id="workItemDropdownMenu">
                    <div class="nav-dropdown-content">
                        <!-- Menu Item 1 -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('work-items.index') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Lihat Item Pekerjaan
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>

                        <!-- Menu Item 2 -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('material-calculations.create') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Hitung Item Pekerjaan
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>

                        <!-- Menu Item 3 -->
                        <div class="dropdown-item-parent">
                            <a href="https://docs.google.com/spreadsheets/d/1tsEQ3a4duHw2AROxsbHaz41n3EiwoFQEpqmWc5XdMP4/edit?usp=sharing" target="_blank"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Tambah Item Pekerjaan
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                Tukang
            </a>

            <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                Keahlian
            </a>

            <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">
                Satuan
            </a>

            <!-- Settings Dropdown -->
            <div class="nav-dropdown-wrapper settings-wrapper" style="margin-left: auto;">
                <button type="button" class="nav-link-btn {{ request()->routeIs('settings.*') ? 'active' : '' }}" id="settingsDropdownToggle">
                    Pengaturan<i class="bi bi-caret-right-fill" style="font-size: 10px; opacity: 0.7;"></i>
                </button>

                <div class="nav-dropdown-menu" id="settingsDropdownMenu" style="left: auto; right: 0;">
                    <div class="nav-dropdown-content">
                        <!-- Menu Item: Rekomendasi TerBAIK -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('settings.recommendations.index') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Rekomendasi TerBAIK
                                <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div class="container page-content">

        <div id="toast-container" class="toast-container" role="status" aria-live="polite" aria-atomic="true"></div>
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

    <!-- Floating Modal Global (Unique ID to avoid conflict) -->
    <div id="globalFloatingModal" class="floating-modal global-modal-layer">
        <div class="floating-modal-backdrop"></div>
        <div class="floating-modal-content">
            <div class="floating-modal-header">
                <h2 id="globalModalTitle">Detail Material</h2>
                <button class="floating-modal-close" id="globalCloseModal">&times;</button>
            </div>
            <div class="floating-modal-body" id="globalModalBody">
                <div style="text-align: center; padding: 60px; color: #94a3b8;">
                    <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                    <div style="font-weight: 500;">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom styles per halaman -->
    @stack('styles')

    <!-- Global Sanitizer Script -->
    <script src="{{ asset('js/form-sanitizer.js') }}"></script>

    <!-- Custom scripts per halaman -->
    @stack('scripts')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navOverlay = document.getElementById('navOverlay');

            function closeNav() {
                document.body.classList.remove('nav-open');
            }

            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    document.body.classList.toggle('nav-open');
                });
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
                            if (nextParent) nextParent.querySelector('.dropdown-item-trigger').focus();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const prevParent = parentItems[index - 1];
                            if (prevParent) {
                                prevParent.querySelector('.dropdown-item-trigger').focus();
                            } else {
                                dropdownToggle.focus();
                                closeDropdown();
                            }
                        } else if (e.key === 'ArrowRight' || e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            const firstChild = parent.querySelector('.dropdown-sub-menu input, .dropdown-sub-menu a, .dropdown-sub-menu button');
                            if (firstChild) firstChild.focus();
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
                            if (parentTrigger) parentTrigger.focus();
                        } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                            // Navigate to next item IF it is in the same submenu
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const nextItem = allSubmenuItems[index + 1];
                            if (nextItem && nextItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                nextItem.focus();
                            }
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const prevItem = allSubmenuItems[index - 1];
                            if (prevItem && prevItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                prevItem.focus();
                            }
                        } else if (e.key === 'Enter' && item.tagName === 'INPUT' && item.type === 'checkbox') {
                             // Allow default behavior for checkboxes (toggle)
                             // No preventDefault() here
                        }
                    });
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
            const STORAGE_KEY = 'material_filter_preferences';

            // 1. Load initial state (Visual Only)
            let savedFilter;
            try {
                savedFilter = JSON.parse(localStorage.getItem(STORAGE_KEY)) || { selected: [], order: [] };
            } catch (e) {
                savedFilter = { selected: [], order: [] };
            }

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
                            selectedMaterials.push(toggle.dataset.material);
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


            // --- Global Modal Logic (Unique Scope) ---
            const globalModal = document.getElementById('globalFloatingModal');
            const globalModalBody = document.getElementById('globalModalBody');
            const globalModalTitle = document.getElementById('globalModalTitle');
            const globalCloseBtn = document.getElementById('globalCloseModal');
            const globalBackdrop = globalModal ? globalModal.querySelector('.floating-modal-backdrop') : null;

            function interceptGlobalFormSubmit() {
                if (!globalModalBody) return;
                const form = globalModalBody.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        // Let form submit normally
                    });
                }
            }

            function getGlobalMaterialInfo(url) {
                let materialType = '';
                let action = '';
                let materialLabel = 'Material';

                if (url.includes('/bricks/')) { materialType = 'brick'; materialLabel = 'Bata'; } 
                else if (url.includes('/cats/')) { materialType = 'cat'; materialLabel = 'Cat'; } 
                else if (url.includes('/cements/')) { materialType = 'cement'; materialLabel = 'Semen'; } 
                else if (url.includes('/sands/')) { materialType = 'sand'; materialLabel = 'Pasir'; }
                else if (url.includes('/settings/recommendations')) { materialType = 'recommendations'; materialLabel = 'Setting Rekomendasi'; }

                if (url.includes('/create')) action = 'create';
                else if (url.includes('/edit')) action = 'edit';
                else if (url.includes('/show')) action = 'show';

                return { materialType, action, materialLabel };
            }

            function loadGlobalMaterialFormScript(materialType, modalBodyEl) {
                const scriptProperty = `global${materialType}FormScriptLoaded`;
                const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

                console.log('[Script] Loading script for:', materialType);
                console.log('[Script] Init function name:', initFunctionName);
                console.log('[Script] Script already loaded?', window[scriptProperty]);

                if (!window[scriptProperty]) {
                    const script = document.createElement('script');
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

            function initializeForm(initFunctionName, modalBodyEl) {
                console.log('[Init] Initializing form with function:', initFunctionName);

                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        console.log('[Init] Function exists, calling it...');
                        window[initFunctionName](modalBodyEl); // Pass scope
                    } else {
                        console.error('[Init] Function not found:', initFunctionName);
                    }
                    interceptGlobalFormSubmit();
                }, 150); // Increased timeout slightly for safety
            }

            function closeGlobalModal() {
                if(!globalModal) return;
                globalModal.classList.remove('active');
                document.body.style.overflow = '';
                setTimeout(() => {
                    globalModalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
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

            if (globalModal && globalModalBody && globalModalTitle && globalCloseBtn && globalBackdrop) {
                // Listen specifically for .global-open-modal class
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('.global-open-modal');
                    if (link) {
                        e.preventDefault();
                        const url = link.href;
                        const { materialType, action, materialLabel } = getGlobalMaterialInfo(url);

                        globalModal.classList.add('active');
                        document.body.style.overflow = 'hidden';

                        // Close dropdown menu if exists
                        const dropdownMenu = document.querySelector('.dropdown-menu.show');
                        if(dropdownMenu) dropdownMenu.classList.remove('show');

                        if (action === 'create') {
                            globalModalTitle.textContent = `Tambah ${materialLabel} Baru`;
                            globalCloseBtn.style.display = 'none'; 
                        } else if (action === 'edit') {
                            globalModalTitle.textContent = `Edit ${materialLabel}`;
                            globalCloseBtn.style.display = 'none'; 
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
                                // If we found a form inside a card (and not using special wrapper), we might want the whole card for styling
                                if (contentElement.id !== 'recommendations-content-wrapper') {
                                    const wrapperCard = contentElement.closest('.card');
                                    if (wrapperCard) {
                                        globalModalBody.innerHTML = wrapperCard.outerHTML;
                                    } else {
                                        globalModalBody.innerHTML = contentElement.outerHTML;
                                    }
                                } else {
                                    // For special wrapper, take innerHTML to avoid double wrapping or issues?
                                    // Actually outerHTML is fine, or innerHTML. Let's use outerHTML to keep the ID wrapper.
                                    globalModalBody.innerHTML = contentElement.outerHTML;
                                }

                                console.log('[Modal] Content inserted, loading scripts...');
                                if (materialType && (action === 'create' || action === 'edit' || materialType === 'recommendations')) {
                                    console.log('[Modal] Loading material form script for:', materialType);
                                    loadGlobalMaterialFormScript(materialType, globalModalBody);
                                } else {
                                    console.log('[Modal] Intercepting form submit (no specific material type)');
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
                });

                globalCloseBtn.addEventListener('click', closeGlobalModal);
                globalBackdrop.addEventListener('click', closeGlobalModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && globalModal.classList.contains('active')) {
                        closeGlobalModal();
                    }
                });
            }
        });

        // Global Validation for Dimension and Weight Inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Event delegation to handle both static and dynamic (modal) forms
            document.body.addEventListener('keydown', function(e) {
                const target = e.target;
                
                // Only targeting input elements
                if (target.tagName !== 'INPUT') return;

                // Identify target fields: type="number" OR fields with specific keywords in ID/Name
                // Keywords: dimension, weight, berat, panjang, lebar, tinggi, volume, price, harga
                const isNumericField = target.type === 'number' || 
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.name || '');

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
                    if ([190, 110, 188].includes(e.keyCode)) {
                        // If value already contains . or , prevent adding another
                        if (target.value.includes('.') || target.value.includes(',')) {
                            e.preventDefault();
                        }
                        return; // Allow the first decimal point
                    }

                    // Ensure that it is a number (0-9)
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                }
            });

            // Sanitize paste events to remove non-numeric characters
            document.body.addEventListener('paste', function(e) {
                const target = e.target;
                if (target.tagName !== 'INPUT') return;

                const isNumericField = target.type === 'number' || 
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.name || '');

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

    <!-- Performance Optimization Scripts -->
    <script src="{{ asset('js/search-debounce.js') }}"></script>
    <script src="{{ asset('js/lazy-loading.js') }}"></script>
</body>
</html>
