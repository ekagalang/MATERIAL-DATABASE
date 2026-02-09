@extends('layouts.app')

@section('title', 'Semua Material')

@php
    $grandTotalTopbar = $grandTotal
        ?? collect($materials)->sum(function ($mat) {
            return $mat['db_count'] ?? $mat['count'] ?? 0;
        });
@endphp

@section('topbar-badge')
    <span class="topbar-material-badge">Total: @format($grandTotalTopbar)</span>
@endsection

@section('content')
<!-- Inline script to restore tab ASAP before page render -->
<script>
(function() {
    document.documentElement.classList.add('materials-booting');
    document.documentElement.classList.add('materials-lock');
})();
(function() {
    const savedTab = localStorage.getItem('materialsIndexActiveTab');
    if (savedTab) {
        // Set a flag that will be checked by main script
        window.__materialSavedTab = savedTab;
    }
})();
(function() {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    if (window.location.hash) {
        const h = window.location.hash;

        if (h === '#skip-page') {
            // Aggressively force top for #skip-page and do NOT pass to main logic
            window.__materialSkipPage = true;
            const skipUrl = new URL(window.location.href);
            const skipSortBy = skipUrl.searchParams.get('sort_by');
            const skipSortDirection = skipUrl.searchParams.get('sort_direction');
            if (skipSortBy !== 'brand' || skipSortDirection !== 'asc') {
                skipUrl.searchParams.set('sort_by', 'brand');
                skipUrl.searchParams.set('sort_direction', 'asc');
                skipUrl.hash = '#skip-page';
                window.location.replace(skipUrl.toString());
                return;
            }
            document.documentElement.style.scrollBehavior = 'auto';
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            // Strip hash only for skip-page
            history.replaceState(null, null, window.location.pathname + window.location.search);
            return;
        }

        // PREVENT browser from scrolling to anchor on page load
        // Lock scroll position temporarily
        const scrollY = window.scrollY || window.pageYOffset || 0;
        window.__preventScrollToAnchor = true;
        window.__savedScrollY = scrollY;

        // Disable scroll behaviors that trigger on hash
        document.documentElement.style.scrollBehavior = 'auto';
        document.documentElement.style.scrollPaddingTop = '0';

        // Keep current hash in JS state and handle scroll manually
        window.__materialHash = h;

        // Immediately restore scroll position if browser tries to scroll
        requestAnimationFrame(() => {
            window.scrollTo(0, scrollY);
        });
    }
})();
(function() {
    document.documentElement.classList.toggle('materials-skip-page', !!window.__materialSkipPage);
})();
(function() {
    window.addEventListener('load', function() {
        document.documentElement.classList.remove('materials-booting');
    });
    window.setTimeout(() => {
        document.documentElement.classList.remove('materials-booting');
    }, 2000);
})();
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('materials-lock');
});

(function() {
    function updateCeramicScrollIndicators() {
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-nat .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.ceramic-scroll-cell, .cement-scroll-cell, .sand-scroll-cell, .cat-scroll-cell, .brick-scroll-cell');
            if (!scroller) return;
            const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
            td.classList.toggle('is-scrollable', isScrollable);
            const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
            td.classList.toggle('is-scrolled-end', isScrollable && atEnd);
        });
    }

    function bindCeramicScrollHandlers() {
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-nat .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.ceramic-scroll-cell, .cement-scroll-cell, .sand-scroll-cell, .cat-scroll-cell, .brick-scroll-cell');
            if (!scroller || scroller.__ceramicScrollBound) return;
            scroller.__ceramicScrollBound = true;
            scroller.addEventListener('scroll', updateCeramicScrollIndicators, { passive: true });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCeramicScrollIndicators();
        bindCeramicScrollHandlers();
        requestAnimationFrame(updateCeramicScrollIndicators);
        setTimeout(updateCeramicScrollIndicators, 60);
    });
    window.addEventListener('resize', function() {
        updateCeramicScrollIndicators();
        bindCeramicScrollHandlers();
        requestAnimationFrame(updateCeramicScrollIndicators);
    });
    window.addEventListener('load', function() {
        updateCeramicScrollIndicators();
    });
})();
</script>
<style>
:root {
    --tab-foot-radius: 18px;
    --tab-active-bg: #91C6BC;
    overflow: hidden;
}

/* Global scroll offset untuk fixed topbar */
html {
    scroll-padding-top: 80px;
    scroll-behavior: smooth;
}

html.materials-booting .material-tab-panel,
html.materials-booting .material-tab-action,
html.materials-booting #emptyMaterialState,
html.materials-booting .material-tabs,
html.materials-booting .material-tab-actions {
    opacity: 0;
    visibility: hidden;
}
html.materials-booting .page-content {
    opacity: 0;
    visibility: hidden;
}
@keyframes material-row-blink {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
}
@keyframes material-row-flash {
    0%, 100% { opacity: 0.35; }
    50% { opacity: 0.75; }
}
@keyframes material-tab-blink {
    0%, 100% { box-shadow: 0 0 0 0 rgba(137, 19, 19, 0); }
    50% { box-shadow: 0 0 0 6px rgba(137, 19, 19, 0.35); }
}
.material-tab-btn.material-tab-blink {
    animation: material-tab-blink 1.2s ease-in-out 2;
}
@keyframes material-letter-blink {
    0%, 100% { transform: translateY(0) scale(1); filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15)) grayscale(0%); }
    50% { transform: translateY(-1px) scale(1.08); filter: drop-shadow(0 0 8px rgba(137, 19, 19, 0.45)) grayscale(0%); }
}
.kanggo-img-link.material-letter-blink .kanggo-img {
    animation: material-letter-blink 0.9s ease-in-out 2;
}
  .table-container {
      position: relative;
  }
  /* Single-header styling for Cat & Cement - COMPACT 40px */
  .table-container thead.single-header th {
      height: 40px !important;
      padding: 8px 12px !important;
      line-height: 1.1;
      vertical-align: top !important;
      box-sizing: border-box;
  }
  .table-container thead.single-header th a {
      align-items: flex-start !important;
  }

  /* Double-header styling for Ceramic (and Brick/Sand) - COMPACT 40px total */
  .table-container thead.has-dim-sub th {
      padding: 6px 12px !important;
      line-height: 1.1;
      vertical-align: top !important;
      box-sizing: border-box;
  }

  /* Ensure dim-group-row (first row) has proper height - COMPACT 26px */
  .table-container thead.has-dim-sub tr.dim-group-row th {
      height: 26px !important;
  }

  /* Ensure dim-sub-row (second row) has proper height - COMPACT 14px */
  .table-container thead.has-dim-sub tr.dim-sub-row th {
      height: 14px !important;
      padding: 1px 2px !important;
      font-size: 11px !important;
  }

  /* CRITICAL: Force ALL thead heights to be exactly the same - COMPACT 40px */
  #section-brick .table-container thead,
  #section-sand .table-container thead,
  #section-cat .table-container thead,
  #section-cement .table-container thead,
  #section-nat .table-container thead,
  #section-ceramic .table-container thead {
      height: 40px !important;
  }

  /* Single header rows should fill the full 40px - COMPACT */
  #section-cat .table-container thead.single-header tr th,
  #section-nat .table-container thead.single-header tr th,
  #section-cement .table-container thead.single-header tr th {
      height: 40px !important;
      line-height: 1.2 !important;
      vertical-align: top !important;
      padding: 8px 12px !important;
  }

  /* Double header rows should total 40px (26px + 14px) - COMPACT */
  #section-brick .table-container thead.has-dim-sub tr.dim-group-row th,
  #section-sand .table-container thead.has-dim-sub tr.dim-group-row th,
  #section-ceramic .table-container thead.has-dim-sub tr.dim-group-row th {
      height: 26px !important;
      vertical-align: top !important;
      padding: 6px 12px !important;
  }

  #section-brick .table-container thead.has-dim-sub tr.dim-sub-row th,
  #section-sand .table-container thead.has-dim-sub tr.dim-sub-row th,
  #section-ceramic .table-container thead.has-dim-sub tr.dim-sub-row th {
      height: 14px !important;
      padding: 1px 2px !important;
      font-size: 11px !important;
      vertical-align: top !important;
  }

  /* Only the leftmost and rightmost borders for the dimension group */
  .table-container thead.has-dim-sub tr.dim-sub-row th:first-child {
      border-left: 1px solid #cbd5e1 !important;
  }
  .table-container thead.has-dim-sub tr.dim-sub-row th:last-child {
      border-right: 1px solid #cbd5e1 !important;
  }

  /* CRITICAL: Force ALL thead th to align top */
  #section-brick .table-container thead th,
  #section-sand .table-container thead th,
  #section-cat .table-container thead th,
  #section-cement .table-container thead th,
  #section-nat .table-container thead th,
  #section-ceramic .table-container thead th {
      vertical-align: top !important;
      font-size: 14px !important;
  }
  /* Override global.css - make all tbody td consistent */
  .table-container table {
      border-collapse: separate !important;
      border-spacing: 0 !important;
  }
  .table-container thead th {
      border: 1px solid #cbd5e1 !important;
      z-index: 20; /* Ensure borders sit above content */
  }
  .table-container tbody td {
      border: 1px solid #f1f5f9 !important;
      padding: 14px 16px !important;
      vertical-align: middle !important;
      font-size: 13px !important;
      color: #1e293b !important;
      text-shadow: none !important;
      -webkit-text-stroke: 0 !important;
  }

  /* Force Aksi column width */
  .table-container thead tr:not(.dim-sub-row) th:last-child,
  .table-container tbody td:last-child {
      width: 90px !important;
      min-width: 90px !important;
      max-width: 90px !important;
      text-align: center !important;
  }

  /* Specific overrides for dimension cells - SEMUA MATERIAL */
  .table-container tbody td.dim-cell {
      text-align: center !important;
      font-size: 12px !important;
      width: 40px !important;
      padding: 0 2px !important;
  }

  /* Specific overrides for volume cells - SEMUA MATERIAL */
  .table-container tbody td.volume-cell {
      text-align: right !important;
      font-size: 12px !important;
      padding: 0 8px !important;
      width: auto !important;
  }

  /* CRITICAL: Force consistent cell heights across ALL materials */
  #section-brick .table-container tbody td,
  #section-sand .table-container tbody td,
  #section-cat .table-container tbody td,
  #section-cement .table-container tbody td,
  #section-nat .table-container tbody td,
  #section-ceramic .table-container tbody td {
      height: 35px !important;
      padding: 2px 8px !important;
      font-size: 12px !important;
      line-height: 1.3 !important;
  }

  /* Force dimension cells to be identical across ALL materials */
  #section-brick .table-container tbody td.dim-cell,
  #section-sand .table-container tbody td.dim-cell {
      text-align: center !important;
      font-size: 12px !important;
      width: 40px !important;
      padding: 2px 2px !important;
      height: 35px !important;
  }
  #section-ceramic .table-container tbody td.dim-cell {
      text-align: center !important;
      font-size: 12px !important;
      width: 50px !important;
      min-width: 50px !important;
      max-width: 50px !important;
      padding: 2px 2px !important;
      height: 35px !important;
  }


  /* Ceramic scroll cells: keep text inside cell */
  #section-ceramic .ceramic-scroll-td,
  #section-cement .cement-scroll-td,
  #section-nat .cement-scroll-td,
  #section-sand .sand-scroll-td,
  #section-cat .cat-scroll-td,
  #section-brick .brick-scroll-td {
      position: relative;
      overflow: hidden;
  }
  #section-ceramic .ceramic-scroll-td.is-scrollable::after,
  #section-cement .cement-scroll-td.is-scrollable::after,
  #section-nat .cement-scroll-td.is-scrollable::after,
  #section-sand .sand-scroll-td.is-scrollable::after,
  #section-cat .cat-scroll-td.is-scrollable::after,
  #section-brick .brick-scroll-td.is-scrollable::after {
      content: '...';
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 12px;
      font-weight: 600;
      color: rgba(15, 23, 42, 0.85);
      background: linear-gradient(90deg, rgba(248, 250, 252, 0) 0%, rgba(248, 250, 252, 0.95) 40%, rgba(248, 250, 252, 1) 100%);
      padding-left: 8px;
      pointer-events: none;
  }
  #section-ceramic .ceramic-scroll-td.is-scrolled-end::after,
  #section-cement .cement-scroll-td.is-scrolled-end::after,
  #section-nat .cement-scroll-td.is-scrolled-end::after,
  #section-sand .sand-scroll-td.is-scrolled-end::after,
  #section-cat .cat-scroll-td.is-scrolled-end::after,
  #section-brick .brick-scroll-td.is-scrolled-end::after {
      opacity: 0;
  }
  #section-ceramic .ceramic-scroll-cell,
  #section-cement .cement-scroll-cell,
  #section-nat .cement-scroll-cell,
  #section-sand .sand-scroll-cell,
  #section-cat .cat-scroll-cell,
  #section-brick .brick-scroll-cell {
      display: block;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: none;
      scrollbar-color: transparent transparent;
  }
  #section-ceramic .ceramic-scroll-cell::-webkit-scrollbar,
  #section-cement .cement-scroll-cell::-webkit-scrollbar,
  #section-nat .cement-scroll-cell::-webkit-scrollbar,
  #section-sand .sand-scroll-cell::-webkit-scrollbar,
  #section-cat .cat-scroll-cell::-webkit-scrollbar,
  #section-brick .brick-scroll-cell::-webkit-scrollbar {
      height: 0;
  }

  /* Force volume cells to be identical */
  #section-brick .table-container tbody td.volume-cell,
  #section-sand .table-container tbody td.volume-cell {
      text-align: right !important;
      font-size: 12px !important;
      padding: 2px 8px !important;
      height: 35px !important;
  }

  .btn-group-compact {
      display: inline-flex;
      align-items: center;
      border-radius: 0;
      overflow: visible;
      box-shadow: none;
      background: transparent;
  }
  .btn-group-compact .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 22px;
      width: 26px;
      padding: 0;
      margin: 0;
      border-radius: 0 !important;
      font-size: 12px;
      line-height: 1;
      font-weight: normal !important;
      -webkit-text-stroke: 0 !important;
      text-shadow: none !important;
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
  }
  .btn-group-compact .btn-action:hover {
      background: transparent !important;
      box-shadow: none !important;
  }
  .btn-group-compact .btn-action {
      color: #0f172a !important;
  }
  .btn-group-compact .btn-action.btn-primary-glossy {
      color: #0f172a !important;
  }
  .btn-group-compact .btn-action.btn-warning {
      color: #b45309 !important;
  }
  .btn-group-compact .btn-action.btn-danger {
      color: #b91c1c !important;
  }
  .btn-group-compact .btn-action i::before {
      -webkit-text-stroke: 0 !important;
  }
  .btn-group-compact .btn-action:first-child {
      border-top-left-radius: 999px !important;
      border-bottom-left-radius: 999px !important;
  }
  .btn-group-compact .btn-action:last-child {
      border-top-right-radius: 999px !important;
      border-bottom-right-radius: 999px !important;
  }
  .btn-group-compact .btn-action + .btn-action {
      border-left: 1px solid rgba(255, 255, 255, 0.35);
  }
.material-row-outline {
    position: absolute;
    border: 2px solid #891313;
    border-radius: 6px;
    box-sizing: border-box;
    pointer-events: none;
    animation: material-row-blink 1.2s ease-in-out 0s 2;
    z-index: 5;
}
.material-row-sweep {
    position: absolute;
    border-radius: 6px;
    pointer-events: none;
    background: rgba(37, 99, 235, 0.28);
    opacity: 0.35;
    animation: material-row-flash 0.9s ease-in-out 0s 2 forwards;
    z-index: 4;
}
.material-row-new td {
    background-color: rgba(37, 99, 235, 0.18) !important;
    transition: background-color 0.2s ease;
}
/* --- CERAMIC STICKY --- */
#section-ceramic .ceramic-sticky-col {
    position: sticky;
    background: #ffffff;
    z-index: 3;
}
#section-ceramic thead .ceramic-sticky-col {
    z-index: 7;
}
#section-ceramic .ceramic-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}

/* --- CAT STICKY --- */
#section-cat .cat-sticky-col {
    position: sticky;
    background: #ffffff;
    z-index: 3;
}
#section-cat thead .cat-sticky-col {
    z-index: 7;
}
#section-cat .cat-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}

/* --- CEMENT STICKY --- */
#section-cement .cement-sticky-col {
    position: sticky;
    background: #ffffff;
    z-index: 3;
}
#section-nat .cement-sticky-col {
    position: sticky;
    background: #ffffff;
    z-index: 3;
}
#section-cement thead .cement-sticky-col {
    z-index: 7;
}
#section-nat thead .cement-sticky-col {
    z-index: 7;
}
#section-cement .cement-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}
#section-nat .cement-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}


.material-search-hit {
    background-color: #2563eb;
    color: #ffffff;
    border-radius: 2px;
    padding: 0 2px;
}
.material-search-jump,
.material-search-jump a {
    cursor: pointer;
}
.material-search-jump a {
    text-decoration: underline dotted;
    text-underline-offset: 4px;
}
  .material-footer-sticky {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      min-height: 72px;
      z-index: 1;
      background: transparent;
      border-top: none;
      box-shadow: none;
      padding: 6px 0 10px;
  }
  .material-footer-count {
      display: inline-block;
      transform: translateY(2px);
      color: #91c6bc !important;
      -webkit-text-stroke: 0 !important;
      font-weight: var(--special-font-weight) !important;
      text-shadow: none !important;
  }
  .material-footer-left {
      display: flex;
      align-items: center;
      gap: 3px;
  }
  .material-footer-right {
      display: flex;
      align-items: center;
      gap: 8px;
  }
  .material-footer-hex {
      width: 44px !important;
      height: 44px !important;
  }
  .material-footer-hex-inner {
      width: 36px !important;
  }
  .material-footer-count {
      font-size: 18px !important;
      font-weight: bold !important;
  }
  .material-footer-label {
      font-size: 10px;
      line-height: 1.2;
      text-align: center;
      color: var(--text-color) !important;
      font-weight: 700 !important;
  }
  .material-footer-hex img {
      width: 44px !important;
      height: 44px !important;
  }
  .material-footer-hex-block {
      justify-content: center !important;
      gap: 2px;
  }
  .material-footer-sticky .kanggo-logo img {
      height: 50px !important;
  }
  .material-footer-sticky .kanggo-letters {
      height: 50px !important;
  }
  .material-footer-sticky .kanggo-img-link img {
      height: 17px !important;
  }
  html.materials-skip-page .kanggo-img-link:not(.current) .kanggo-img {
      filter: grayscale(100%) !important;
      opacity: 0.6 !important;
  }
  html.materials-skip-page .kanggo-img-link:not(.current):hover .kanggo-img {
      filter: grayscale(100%) !important;
      opacity: 0.6 !important;
  }
  html.materials-lock body,
  body.materials-lock {
      overflow: hidden;
  }
  html.materials-lock .page-content,
  body.materials-lock .page-content {
      height: calc(100vh - 70px);
      overflow: hidden;
  }
  html.materials-lock .material-tab-wrapper,
  body.materials-lock .material-tab-wrapper {
      display: flex;
      flex-direction: column;
      height: calc(100vh - 90px);
  }
  html.materials-lock .material-tab-panel,
  body.materials-lock .material-tab-panel {
      flex: 1 1 auto;
      height: auto;
  }
  html.materials-lock .material-tab-card,
  body.materials-lock .material-tab-card {
      display: flex;
      flex-direction: column;
      height: 100%;
  }
  html.materials-lock .material-tab-card .table-container,
  body.materials-lock .material-tab-card .table-container {
      flex: 1 1 auto;
      overflow-y: auto;
  }
  /* Utility classes for removing borders in grouped columns - Ultra High Specificity */
  .material-tab-panel .table-container tbody td.border-left-none {
      border-left: 0 !important;
      border-left-style: none !important;
      border-left-color: transparent !important;
  }
  .material-tab-panel .table-container tbody td.border-right-none {
      border-right: 0 !important;
      border-right-style: none !important;
      border-right-color: transparent !important;
  }
  @media (max-width: 992px) {
      html.materials-lock .material-tab-wrapper,
      body.materials-lock .material-tab-wrapper {
          height: calc(100vh - 170px);
      }
  }
/* Sticky footer styles removed - footer is now static */
.material-tab-header {
    gap: 12px;
    padding-top: 10px;
    padding-bottom: 2px;
    margin-bottom: 0;
}
.material-tab-header::after {
    border-bottom: 0;
}
.material-tab-header .material-tabs {
    position: relative;
    flex: 1 1 67%;
    width: auto;
    top: 0;
    bottom: 0;
    padding-top: 2px;
    z-index: 40;
}
.material-tab-header .material-settings-menu {
    left: 0;
    right: auto;
    width: max-content !important;
    min-width: 400px !important;
    max-width: 560px !important;
    top: 100% !important;
    margin-top: -1px;
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
    transform: translateY(-5px) !important;
    z-index: 50 !important;
}

/* Connect dropdown menu to the filter button seamlessly when active */
.material-tab-header .material-settings-menu.active {
    border-top: none !important;
    transform: translateY(0) !important;
    box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.15) !important;
    margin-top: 0 !important;
}
.material-tab-header .material-settings-dropdown {
    position: relative;
    flex: 0 0 auto;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    z-index: 45;
}
/* Override global.css for filter button - use original yellow color */
.material-tab-header .material-settings-btn {
    border-bottom: none !important;
    border-radius: 12px 12px 0 0 !important;
    background: transparent !important;
    color: var(--text-color) !important;
    transition: background 0.2s ease, color 0.2s ease !important;
    height: var(--tab-height) !important;
    min-width: 100px !important;
    padding: 0 var(--tab-padding-x) !important;
    position: relative !important;
    z-index: 1 !important;
    box-shadow: none !important;
}

.material-tab-header .material-settings-btn:hover {
    background: #fff5f5 !important;
    color: #891313 !important;
    box-shadow: none !important;
}

.material-tab-header .material-settings-btn.active {
    background: #F6F3C2 !important;
    color: #891313 !important;
    border-bottom: none !important;
    font-weight: 700 !important;
    z-index: 5 !important;
    box-shadow: none !important;
}

/* When dropdown is open, make borders thicker to match menu */
.material-tab-header .material-settings-btn.dropdown-open {
    border-width: 2px !important;
    border-bottom: none !important;
}

.material-tab-header .material-settings-btn i {
    font-size: 14px !important;
    color: inherit !important;
}

/* CRITICAL: Filter button NEVER has left foot - override all */
.material-tab-header .material-settings-btn::before,
.material-tab-header .material-settings-btn.active::before,
.material-tab-header .material-settings-btn.active:not(.dropdown-open)::before,
.material-tabs.only-filter .material-settings-btn.active::before,
.material-settings-btn.active.last-visible::before,
.material-settings-btn.active.first-visible::before {
    content: none !important;
    display: none !important;
}

/* Add concave feet to filter button when active - ONLY RIGHT FOOT */
/* Only show RIGHT foot when dropdown is NOT open */
.material-tab-header .material-settings-btn.active:not(.dropdown-open)::after {
    content: "" !important;
    position: absolute;
    bottom: -2px;
    left: calc(100% - 1px);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background:
        radial-gradient(
            circle at 100% 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            #F6F3C2 var(--tab-foot-radius)
        );
    background-position: bottom left;
    pointer-events: none;
    z-index: 5;
}

/* Keep right foot visible for all filter states */
.material-settings-btn.active.last-visible::after,
.material-settings-btn.active.first-visible::after {
    content: "" !important;
}
.material-tab-actions {
    flex: 0 0 33%;
    max-width: 33%;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    margin-left: auto;
    position: relative;
    z-index: 10;
}
.material-tab-action {
    display: none;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    width: 100%;
}
.material-tab-action.active {
    display: flex;
    --tab-border-color: #91C6BC;
    position: relative;
    background: #91C6BC;
    border: 2px solid #91C6BC;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 8px 12px 4px;
    margin-bottom: -1px;
    z-index: 11;
    overflow: visible !important;
    min-height: 48px;
    box-sizing: border-box;
    align-items: flex-end;
    transform: translateY(4px);
}
.material-tab-actions {
    overflow: visible !important;
}
.material-tab-header {
    overflow: visible !important;
}
.material-tabs {
    overflow: visible !important;
}
.material-tab-btn {
    overflow: visible !important;
    position: relative;
}
.material-tab-btn.active {
    --tab-border-color: #91C6BC;
}
.material-tab-badge {
    position: absolute;
    top: -4px;
    right: 8px;
    background: #ef4444;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    padding: 3px 6px;
    border-radius: 999px;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35);
    z-index: 20;
    pointer-events: none;
    white-space: nowrap;
}
.topbar-material-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 10px;
    background: #ef4444;
    color: #ffffff;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
}
/* Kaki cekung untuk TAB MATERIAL (Bata, Cat, dll) - BENAR SEKARANG */
.material-tab-btn.active::before {
    content: "" !important;
    position: absolute;
    bottom: -2px;
    right: calc(100% - 1px);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background:
        radial-gradient(
            circle at 0 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            var(--tab-active-bg) var(--tab-foot-radius)
        );
    background-position: bottom right;
    pointer-events: none;
    z-index: 5;
}
.material-tab-btn.active::after {
    content: "" !important;
    position: absolute;
    bottom: -2px;
    left: calc(100% - 1px);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background:
        radial-gradient(
            circle at 100% 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            var(--tab-active-bg) var(--tab-foot-radius)
        );
    background-position: bottom left;
    pointer-events: none;
    z-index: 5;
}

/* Tab PERTAMA yang visible - cuma kaki KANAN (hapus kaki kiri) */
.material-tab-btn.active.first-visible::before {
    content: none !important;
}

/* Tab TERAKHIR yang visible - cuma kaki KIRI (hapus kaki kanan) */
.material-tab-btn.active.last-visible::after {
    content: none !important;
}

/* Tab sebelum filter button - cuma kaki KIRI (hapus kaki kanan) */
.material-tab-btn.active.before-filter::after {
    content: none !important;
}

/* Kaki cekung untuk SEARCH ACTION ROW - KIRI SAJA */
.material-tab-action.active::before {
    content: "";
    position: absolute;
    bottom: 2px;
    right: calc(100%);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background:
        radial-gradient(
            circle at 0 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            var(--tab-active-bg) var(--tab-foot-radius)
        );
    background-position: bottom right;
    pointer-events: none;
    z-index: 5;
}
/* Hapus kaki kanan untuk search action row */
.material-tab-action.active::after {
    content: none !important;
}
.material-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1 1 auto;
    min-width: 0;
    margin: 0;
}
.material-tab-action > .btn {
    flex: 0 0 auto;
}
.material-search-input {
    flex: 1 1 auto;
    min-width: 0;
    position: relative;
    padding: 0;
}
.material-search-input input {
    width: 100%;
    height: 34px;
    padding: 4px 10px 4px 30px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.2s ease;
}
.material-search-input i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: var(--text-color);
    opacity: 0.8;
    z-index: 2;
    pointer-events: none;
}
.material-tab-action .btn {
    height: 34px;
    padding: 6px 10px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
}
@media (max-width: 1200px) {
    .material-tab-header {
        flex-wrap: wrap;
    }
    .material-tab-header .material-tabs,
    .material-search-input {
      position: relative;
  }
  .material-search-input input {
      padding-right: 36px;
  }
  .material-search-reset {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      color: #64748b;
      text-decoration: none;
      background: rgba(148, 163, 184, 0.15);
  }
  .material-search-reset:hover {
      color: #0f172a;
      background: rgba(148, 163, 184, 0.25);
  }
  .material-tab-actions {
        flex: 1 1 100%;
        max-width: 100%;
    }
    .material-tab-actions {
        margin-top: 8px;
    }
}
/* Make table scrollable with sticky header */
  .table-container {
      overflow-y: auto;
      overflow-x: auto;
      scroll-padding-top: 80px;
      scroll-behavior: smooth;
  }

  .table-container thead {
      position: sticky;
      top: 0;
      z-index: 10;
      background: white;
  }

  /* Ensure the entire page fits the screen */
  .material-tab-wrapper {
      display: flex;
      flex-direction: column;
  }

  .material-tab-panel {
      display: flex;
      flex-direction: column;
      flex: 1;
      overflow: hidden;
  }

  .material-tab-card {
      display: flex;
      flex-direction: column;
      flex: 1;
      overflow: hidden;
  }
</style>


    @php
        $availableTypes = collect($materials)->pluck('type')->toArray();
        // Check if there's a saved tab from localStorage (set by inline script)
        $activeTab = request('tab');
        if (!$activeTab && !empty($availableTypes)) {
            // Will be overridden by JavaScript if localStorage has value
            $activeTab = $materials[0]['type'] ?? null;
        }
        if (!in_array($activeTab, $availableTypes)) {
            $activeTab = $materials[0]['type'] ?? null;
        }
    @endphp
    <div class="material-tab-wrapper">
        <div class="material-tab-header">
            <div class="material-tabs">
                @foreach($materials as $material)
                    <button type="button"
                            class="material-tab-btn {{ $material['type'] === $activeTab ? 'active' : '' }}"
                            data-tab="{{ $material['type'] }}"
                            data-search-count="{{ $material['count'] }}"
                            aria-selected="{{ $material['type'] === $activeTab ? 'true' : 'false' }}">
                        <span>{{ $material['label'] }}</span>
                        <span class="material-tab-badge">
                            @format($material['db_count'] ?? $material['count'])
                        </span>
                    </button>
                @endforeach

                <div class="material-settings-dropdown">
                    <button type="button" id="materialSettingsToggle" class="material-settings-btn">
                        <i class="bi bi-sliders"></i>
                        <span>Filter</span>
                    </button>
                    <div class="material-settings-menu" id="materialSettingsMenu">
                        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); background: #f5f2c1;">
                            <div class="dropdown-header" style="margin-bottom: 0;">Pilih Material yang Ditampilkan</div>
                        </div>
                        <div class="material-settings-grid">
                            @foreach($allSettings as $setting)
                                <label class="material-setting-item" for="material-checkbox-{{ $setting->material_type }}">
                                    <input type="checkbox"
                                           id="material-checkbox-{{ $setting->material_type }}"
                                           class="material-setting-input"
                                           data-material="{{ $setting->material_type }}"
                                           autocomplete="off">
                                    <span class="material-setting-checkbox"></span>
                                    <span class="material-setting-label">{{ \App\Models\MaterialSetting::getMaterialLabel($setting->material_type) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="nav-material-actions" style="border-top: 1px solid rgba(0, 0, 0, 0.05); margin-top: 0; background: #f5f2c1; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                            <button type="button" id="resetMaterialFilter" class="btn btn-sm nav-material-reset" style="width: 100%;">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="material-tab-actions">
                @foreach($materials as $material)
                    <div class="material-tab-action {{ $material['type'] === $activeTab ? 'active' : '' }}" data-tab="{{ $material['type'] }}">
                        <form action="{{ route('materials.index') }}" method="GET" class="material-search-form manual-search" data-search-manual="true">
                            <input type="hidden" name="tab" value="{{ $material['type'] }}">
                            <div class="material-search-input">
                                <i class="bi bi-search"></i>
                                <input type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Cari {{ strtolower($material['label']) }}...">
                            </div>
                            <button type="submit" class="btn btn-primary-glossy">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('materials.index', ['tab' => $material['type']]) }}" class="btn btn-secondary-glossy material-search-reset-btn">
                                    <i class="bi bi-x-lg"></i> Reset
                                </a>
                            @endif
                        </form>
                        <a href="{{ route($material['type'] . 's.create') }}"
                           class="btn btn-glossy open-modal">
                            <i class="bi bi-plus-lg"></i> Tambah {{ $material['label'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Empty state when no materials selected -->
        <div id="emptyMaterialState" style="display: block; padding: 60px 40px; text-align: center; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; margin-top: 20px;">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">📋</div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 18px; font-weight: 700;">Tidak Ada Material yang Ditampilkan</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Pilih material yang ingin ditampilkan dari dropdown <strong>"Filter"</strong> di atas.</p>
        </div>

        @if(count($materials) > 0)
            @foreach($materials as $material)
                {{-- Removed 'material-section' class to prevent global CSS margin-top conflict which causes gap between tab and content --}}
                <div class="material-tab-panel {{ $material['type'] === $activeTab ? 'active' : 'hidden' }}" data-tab="{{ $material['type'] }}" id="section-{{ $material['type'] }}" style="margin-bottom: 24px;">
                    <div class="material-tab-card">
                    
                    @include('materials.partials.table', ['material' => $material, 'grandTotal' => $grandTotal])
                    </div>
                </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Tidak Ada Material yang Ditampilkan</p>
            <p style="font-size: 14px; color: #94a3b8;">Pilih material yang ingin ditampilkan dari dropdown <strong>"Filter"</strong> di atas.</p>
        </div>
    @endif
    </div>
@endsection

@section('modals')
<!-- Material Choice Modal -->
<div id="materialChoiceModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content" style="width: 600px;">
        <div class="floating-modal-header">
            <h2>Pilih Jenis Material</h2>
            <button class="floating-modal-close" id="closeMaterialChoiceModal">&times;</button>
        </div>
        <div class="floating-modal-body">
            <p style="color: #64748b; margin-bottom: 24px;">Pilih jenis material yang ingin Anda tambahkan:</p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <a href="{{ route('bricks.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🧱</div>
                    <div class="material-choice-label">Bata</div>
                    <div class="material-choice-desc">Tambah data bata</div>
                </a>
                <a href="{{ route('cats.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🎨</div>
                    <div class="material-choice-label">Cat</div>
                    <div class="material-choice-desc">Tambah data cat</div>
                </a>
                <a href="{{ route('cements.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🏗️</div>
                    <div class="material-choice-label">Semen</div>
                    <div class="material-choice-desc">Tambah data semen</div>
                </a>
                <a href="{{ route('nats.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🧱</div>
                    <div class="material-choice-label">Nat</div>
                    <div class="material-choice-desc">Tambah data nat</div>
                </a>
                <a href="{{ route('sands.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">⛱️</div>
                    <div class="material-choice-label">Pasir</div>
                    <div class="material-choice-desc">Tambah data pasir</div>
                </a>
                <a href="{{ route('ceramics.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🟫</div>
                    <div class="material-choice-label">Keramik</div>
                    <div class="material-choice-desc">Tambah data keramik</div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Floating Modal -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
        <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Detail Material</h2>
            <button class="floating-modal-close" id="floatingCloseModal">&times;</button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <div style="text-align: center; padding: 60px; color: #94a3b8;">
                <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                <div style="font-weight: 500;">Loading...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/api-helper.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Safety check: Unlock scroll on load
    document.body.style.overflow = '';

    // Load saved filter from localStorage
    const STORAGE_KEY = 'materials_index_filter_preferences';
    const ACTIVE_TAB_STORAGE_KEY = 'materialsIndexActiveTab';
    const LETTER_HISTORY_STORAGE_KEY = 'materialsIndexLetterHistory';
    let savedFilter = null;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        savedFilter = stored ? JSON.parse(stored) : { selected: [], order: [] };
    } catch (e) {
        savedFilter = { selected: [], order: [] };
    }
    const searchQuery = @json(request('search'));
    const searchQueryRaw = typeof searchQuery === 'string' ? searchQuery.trim() : '';
    const normalizedSearchQuery = searchQueryRaw.toLowerCase();
    const hasSearchQuery = normalizedSearchQuery.length > 0;
    const sessionNewMaterialData = @json(session('new_material'));
    const sessionUpdatedMaterialData = @json(session('updated_material'));
    const focusTypeFromUrl = new URLSearchParams(window.location.search).get('_focus_type');
    const focusIdFromUrl = new URLSearchParams(window.location.search).get('_focus_id');
    const queryFocusMaterialData = (focusTypeFromUrl && focusIdFromUrl)
        ? { type: String(focusTypeFromUrl), id: String(focusIdFromUrl) }
        : null;
    let pendingMaterialData = null;
    try {
        const pendingRaw = sessionStorage.getItem('pendingMaterialFocus');
        pendingMaterialData = pendingRaw ? JSON.parse(pendingRaw) : null;
    } catch (e) {
        pendingMaterialData = null;
    }
    const newMaterialData = queryFocusMaterialData || sessionNewMaterialData || sessionUpdatedMaterialData || pendingMaterialData;
    let materialOrder = savedFilter.order || [];
    const navBlinkMaterial = localStorage.getItem('materialNavSearchBlink');
    const navSearchType = localStorage.getItem('materialNavSearchType');
    const wasSkipPageOnLoad = !!window.__materialSkipPage;
    let letterHistory = {};
    try {
        const storedLetterHistory = localStorage.getItem(LETTER_HISTORY_STORAGE_KEY);
        letterHistory = storedLetterHistory ? JSON.parse(storedLetterHistory) : {};
    } catch (e) {
        letterHistory = {};
    }

    function saveLetterHistory() {
        try {
            localStorage.setItem(LETTER_HISTORY_STORAGE_KEY, JSON.stringify(letterHistory));
        } catch (e) {
            // Ignore storage errors
        }
    }

    function rememberLetterForTab(tabType, hash) {
        const tab = String(tabType || '').trim();
        const value = String(hash || '').trim();
        if (!tab || !value.startsWith('#')) return;
        letterHistory[tab] = value;
        saveLetterHistory();
    }

    function getRememberedLetterForTab(tabType) {
        const tab = String(tabType || '').trim();
        if (!tab) return '';
        const remembered = letterHistory[tab];
        return typeof remembered === 'string' ? remembered : '';
    }

    if (navBlinkMaterial) {
        if (!Array.isArray(savedFilter.selected)) {
            savedFilter.selected = [];
        }
        if (!savedFilter.selected.includes(navBlinkMaterial)) {
            savedFilter.selected.push(navBlinkMaterial);
        }
        if (!Array.isArray(materialOrder)) {
            materialOrder = [];
        }
        materialOrder = materialOrder.filter(type => type !== navBlinkMaterial);
        materialOrder.unshift(navBlinkMaterial);
    }

    if (newMaterialData && newMaterialData.type) {
        if (!Array.isArray(savedFilter.selected)) {
            savedFilter.selected = [];
        }
        if (!savedFilter.selected.includes(newMaterialData.type)) {
            savedFilter.selected.push(newMaterialData.type);
        }
        // Keep existing tab order unchanged after create/edit.
        // We only need to ensure the tab is visible and can be focused.
    }

    // Material Settings Dropdown
    const settingsToggle = document.getElementById('materialSettingsToggle');
    const settingsMenu = document.getElementById('materialSettingsMenu');

    if (settingsToggle && settingsMenu) {
        settingsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = settingsMenu.classList.contains('active');

            if (isActive) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
                settingsToggle.classList.remove('dropdown-open');
            } else {
                settingsMenu.classList.add('active');
                settingsToggle.classList.add('active');
                settingsToggle.classList.add('dropdown-open');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!settingsToggle.contains(e.target) && !settingsMenu.contains(e.target)) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
                settingsToggle.classList.remove('dropdown-open');
            }
        });

        // Prevent dropdown from closing when clicking inside menu
        settingsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Material Toggle Checkboxes - Save to localStorage
    const toggleCheckboxes = document.querySelectorAll('.material-setting-input');
    const allTabButtons = document.querySelectorAll('.material-tab-btn');
    const allTabPanels = document.querySelectorAll('.material-tab-panel');
    const allTabActions = document.querySelectorAll('.material-tab-action');

    // Handle Checkbox UI state (add/remove checked class)
    function updateCheckboxUI(checkbox) {
        const parent = checkbox.closest('.material-setting-item');
        if (parent) {
            if (checkbox.checked) {
                parent.classList.add('checked');
            } else {
                parent.classList.remove('checked');
            }
        }
    }

    // Tab switching function (declared early to avoid reference errors)
    const tabButtons = Array.from(allTabButtons);
    const tabPanels = Array.from(allTabPanels);

    // Sticky footer functionality removed - footer is now static
    // let stickyTicking = false;
    // function updateFooterStickyState() { ... }
    // function requestStickyUpdate() { ... }

    function setActiveTab(materialType) {
        console.log('[Tab] Setting active tab:', materialType);

        // Deactivate all
        document.querySelectorAll('.material-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.material-tab-panel').forEach(panel => {
            panel.classList.remove('active');
            panel.classList.add('hidden'); // Ensure hidden class is added
        });
        // Deactivate all tab actions (search form + Tambah button)
        document.querySelectorAll('.material-tab-action').forEach(action => action.classList.remove('active'));

        // Activate target
        const btn = document.querySelector(`.material-tab-btn[data-tab="${materialType}"]`);
        const panel = document.getElementById(`section-${materialType}`);
        const tabAction = document.querySelector(`.material-tab-action[data-tab="${materialType}"]`);

        if (btn && panel) {
            btn.classList.remove('hidden');
            btn.classList.add('active');

            panel.classList.remove('hidden'); // Remove hidden first
            // Small delay to allow display:block to apply before adding active class (for transitions if any)
            // But for simple visibility switch, direct add is fine.
            panel.classList.add('active');

            // Activate corresponding tab action
            if (tabAction) {
                tabAction.classList.add('active');
            }

            // Lazy Load Logic
            const loadingEl = panel.querySelector('.material-tab-loading');
            if (loadingEl) {
                const url = loadingEl.getAttribute('data-url');
                const card = panel.querySelector('.material-tab-card');
                
                // Only fetch if URL exists and not already fetching
                if (url && card && !card.dataset.fetching) {
                    card.dataset.fetching = "true";
                    
                    fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        card.innerHTML = html;

                        // Re-initialize sticky headers
                        if (typeof applyAllStickyOffsets === 'function') {
                            window.requestAnimationFrame(() => applyAllStickyOffsets());
                        }

                        // Ensure default letter state is applied after lazy content is rendered.
                        updateActivePaginationLetter();
                        
                        // Re-run search setup if search query exists
                        // Note: We might need to extract this logic to be reusable
                        if (typeof setupSearchEnhancements === 'function' && typeof hasSearchQuery !== 'undefined' && hasSearchQuery) {
                            setupSearchEnhancements();
                        }

                        if (
                            newMaterialData &&
                            newMaterialData.type &&
                            materialType === newMaterialData.type &&
                            !newMaterialHandled
                        ) {
                            window.setTimeout(focusNewMaterialRow, 80);
                        }
                    })
                    .catch(err => {
                        console.error('Failed to load tab:', err);
                        loadingEl.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><div style="font-size: 32px; margin-bottom: 8px;">⚠️</div><div>Gagal memuat data.</div><button class="btn btn-sm btn-outline-danger mt-2" onclick="location.reload()">Coba Lagi</button></div>';
                    })
                    .finally(() => {
                        delete card.dataset.fetching;
                    });
                }
            }

            // FIX: Reset scroll position to prevent sticky column flicker
            const tableContainer = panel.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.scrollLeft = 0;
            }

            // Recalculate sticky offsets when tab becomes visible
            if (typeof applyAllStickyOffsets === 'function') {
                window.requestAnimationFrame(() => applyAllStickyOffsets());
            }

            try {
                localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, materialType);
                const url = new URL(window.location.href);
                url.searchParams.set('tab', materialType);
                history.replaceState(null, null, url.toString());
                localStorage.setItem('lastMaterialsUrl', url.toString());
            } catch (e) {
                // Ignore
            }
        }
    }

    // Function to save filter preferences to localStorage
    function saveFilterToLocalStorage(selected, order) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                selected: selected,
                order: order
            }));
        } catch (e) {
            console.error('Failed to save filter to localStorage:', e);
        }
    }

    // Function to reorder tabs based on materialOrder
    function reorderTabs(orderOverride = null) {
        const tabContainer = document.querySelector('.material-tabs');
        if (!tabContainer) return;
        
        const settingsDropdown = tabContainer.querySelector('.material-settings-dropdown');
        const order = Array.isArray(orderOverride) && orderOverride.length ? orderOverride : materialOrder;

        // Create a map of current tab buttons
        const tabButtons = {};

        // Always remove all position classes first
        allTabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            tabButtons[tabType] = btn;
            btn.classList.remove('first-visible', 'last-visible', 'before-filter');
        });

        // Also remove classes from settings button
        const settingsBtn = settingsDropdown?.querySelector('.material-settings-btn');
        if (settingsBtn) {
            settingsBtn.classList.remove('first-visible', 'last-visible');
        }

        // Only reorder and add classes if materialOrder has items
        if (order.length > 0) {
            // Reorder based on order
            order.forEach((type, index) => {
                if (tabButtons[type]) {
                    tabContainer.appendChild(tabButtons[type]);

                    // Add position classes for concave legs styling
                    if (index === 0) {
                        tabButtons[type].classList.add('first-visible');
                    }
                    // If this is the last tab and settings dropdown exists, mark as before-filter
                    if (index === order.length - 1 && settingsDropdown) {
                        tabButtons[type].classList.add('before-filter');
                    }
                    // Only add last-visible if there's no settings dropdown
                    if (index === order.length - 1 && !settingsDropdown) {
                        tabButtons[type].classList.add('last-visible');
                    }
                }
            });

            // Always move settings dropdown to the end
            if (settingsDropdown) {
                tabContainer.appendChild(settingsDropdown);
                // Settings button is always last visible when present
                if (settingsBtn) {
                    settingsBtn.classList.add('last-visible');
                }
            }
        } else {
            // If no material tabs visible, only filter button is present
            // Move settings dropdown to container (it's the only visible element)
            if (settingsDropdown) {
                tabContainer.appendChild(settingsDropdown);
                // When only filter is visible, it's both first and last
                // But CSS .material-tabs.only-filter handles removing the feet
                if (settingsBtn) {
                    settingsBtn.classList.add('first-visible', 'last-visible');
                }
            }
        }
    }

    function getTabSearchCounts() {
        const counts = {};
        allTabButtons.forEach(btn => {
            const type = btn.getAttribute('data-tab');
            const rawCount = btn.getAttribute('data-search-count');
            const count = Number.parseInt(rawCount, 10);
            counts[type] = Number.isNaN(count) ? 0 : count;
        });
        return counts;
    }

    function getSearchOrderedTabs(checkedMaterials, counts) {
        const baseOrder = materialOrder.length ? materialOrder.slice() : checkedMaterials.slice();
        const filtered = baseOrder.filter(type => checkedMaterials.includes(type));
        const orderIndex = new Map(filtered.map((type, index) => [type, index]));
        return filtered.slice().sort((a, b) => {
            const diff = (counts[b] || 0) - (counts[a] || 0);
            if (diff !== 0) return diff;
            return (orderIndex.get(a) ?? 0) - (orderIndex.get(b) ?? 0);
        });
    }

    // Function to update tab visibility based on checkboxes
    function updateTabVisibility(preferredTab = null) {
        console.log('[updateTabVisibility] Started');
        const checkedMaterials = [];
        const emptyState = document.getElementById('emptyMaterialState');
        const tabContainer = document.querySelector('.material-tabs');

        // First, collect all checked materials
        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            if (checkbox.checked) {
                checkedMaterials.push(materialType);
                // Add to order if not already there
                if (!materialOrder.includes(materialType)) {
                    materialOrder.push(materialType);
                }
            }
        });

        console.log('[updateTabVisibility] Checked materials:', checkedMaterials);
        console.log('[updateTabVisibility] Material order before filter:', [...materialOrder]);

        // Remove unchecked materials from order
        materialOrder = materialOrder.filter(item => checkedMaterials.includes(item));

        console.log('[updateTabVisibility] Material order after filter:', [...materialOrder]);

        // Show/hide empty state
        if (emptyState) {
            if (checkedMaterials.length === 0) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }
        }

        // Use standard materialOrder regardless of search to maintain consistent layout
        const visibleOrder = materialOrder;

        // Reorder tabs based on tick order
        reorderTabs(visibleOrder);

        // Show/hide tabs and panels based on checked materials (in order)
        allTabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            if (checkedMaterials.includes(tabType)) {
                btn.style.display = 'inline-flex';
            } else {
                btn.style.display = 'none';
            }
        });

        allTabPanels.forEach(panel => {
            const panelType = panel.getAttribute('data-tab');
            if (checkedMaterials.includes(panelType)) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        });
        allTabActions.forEach(action => {
            const actionType = action.getAttribute('data-tab');
            if (checkedMaterials.includes(actionType)) {
                action.style.display = '';
            } else {
                action.classList.remove('active');
                action.style.display = 'none';
            }
        });

        // Ensure tab container is always visible so Filter button remains accessible
        if (tabContainer) {
            tabContainer.style.display = 'flex';
            tabContainer.classList.toggle('only-filter', checkedMaterials.length === 0);
        }
        const tabActionsContainer = document.querySelector('.material-tab-actions');
        if (tabActionsContainer) {
            tabActionsContainer.style.display = checkedMaterials.length > 0 ? 'flex' : 'none';
        }

        // Auto-activate tab (prefer saved tab, fallback to first visible)
        if (visibleOrder.length > 0) {
            let tabToActivate = visibleOrder[0];

            const hasPreferredTab = preferredTab && checkedMaterials.includes(preferredTab);

            if (hasPreferredTab) {
                tabToActivate = preferredTab;
            } else if (hasSearchQuery && searchCounts) {
                const firstWithResults = visibleOrder.find(type => (searchCounts[type] || 0) > 0);
                if (firstWithResults) {
                    tabToActivate = firstWithResults;
                }
            }

            setActiveTab(tabToActivate);
        }

        // Save to localStorage
        saveFilterToLocalStorage(checkedMaterials, materialOrder);
        // requestStickyUpdate(); // Removed - sticky footer functionality disabled
    }

    // Listen to checkbox changes FIRST (before restore)
    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            console.log('[Checkbox Change] Material:', checkbox.getAttribute('data-material'), 'Checked:', checkbox.checked);
            updateCheckboxUI(this);
            updateTabVisibility();
        });
    });

    // Restore checkboxes from localStorage
    console.log('[Restore] Saved filter:', savedFilter);
    console.log('[Restore] Initial materialOrder:', [...materialOrder]);

    if (savedFilter.selected && savedFilter.selected.length > 0) {
        console.log('[Restore] Restoring', savedFilter.selected.length, 'materials');
        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            checkbox.checked = savedFilter.selected.includes(materialType);
            updateCheckboxUI(checkbox);
        });
    } else {
        console.log('[Restore] No saved filter, unchecking all');
        // Force uncheck all checkboxes if no saved filter
        toggleCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            updateCheckboxUI(checkbox);
        });
        materialOrder = [];
    }

    // Initialize page state: restore from localStorage or show empty state
    console.log('[Restore] Calling updateTabVisibility');
    const tabFromQuery = new URLSearchParams(window.location.search).get('tab');
    const savedTab = tabFromQuery || window.__materialSavedTab || localStorage.getItem(ACTIVE_TAB_STORAGE_KEY);
    updateTabVisibility(savedTab);
    if (window.__materialSkipPage) {
        const resetSkipPageScroll = () => {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            const activePanel = document.querySelector('.material-tab-panel.active')
                || document.querySelector('.material-tab-panel');
            if (activePanel) {
                const tableContainer = activePanel.querySelector('.table-container');
                if (tableContainer) {
                    tableContainer.scrollTop = 0;
                }
            }
        };

        document.documentElement.style.scrollBehavior = 'auto';
        resetSkipPageScroll();
        window.requestAnimationFrame(() => {
            resetSkipPageScroll();
            document.documentElement.style.scrollBehavior = '';
            document.documentElement.classList.remove('materials-booting');
        });
        window.__materialSkipPage = false;
        document.documentElement.classList.remove('materials-skip-page');
    } else {
        document.documentElement.classList.remove('materials-booting');
    }

    if (navSearchType) {
        const searchTab = navBlinkMaterial || savedTab;
        window.setTimeout(() => {
            highlightMaterialRowByType(searchTab, navSearchType);
        }, 150);
        try {
            localStorage.removeItem('materialNavSearchType');
        } catch (e) {
            // Ignore storage errors
        }
    }

    if (navBlinkMaterial) {
        const blinkTarget = document.querySelector(`.material-tab-btn[data-tab="${navBlinkMaterial}"]`);
        if (blinkTarget) {
            blinkTarget.classList.add('material-tab-blink');
            window.setTimeout(() => {
                blinkTarget.classList.remove('material-tab-blink');
            }, 2400);
        }
        try {
            localStorage.removeItem('materialNavSearchBlink');
        } catch (e) {
            // Ignore storage errors
        }
    }

    // Reset Material Filter Button
    const resetFilterBtn = document.getElementById('resetMaterialFilter');
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function() {
            // Uncheck all checkboxes
            toggleCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                updateCheckboxUI(checkbox);
            });

            // Clear materialOrder
            materialOrder = [];

            // Clear localStorage
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                console.error('Failed to clear localStorage:', e);
            }

            // Update tab visibility (hide all)
            updateTabVisibility();
        });
    }
    
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = modal ? modal.querySelector('.floating-modal-close') : null;
    const backdrop = modal ? modal.querySelector('.floating-modal-backdrop') : null;

    function interceptFormSubmit() {
        if (!modalBody) {
            console.error('[Modal] modalBody not found');
            return;
        }
        const form = modalBody.querySelector('form');
        if (form) {
            console.log('[Modal] Form found:', form.id, 'Action:', form.action);

            // Add hidden input to redirect back to the current page after submit
            let redirectInput = form.querySelector('input[name="_redirect_url"]');
            if (!redirectInput) {
                redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = '_redirect_url';
                form.appendChild(redirectInput);
            }
            // Always update the redirect URL to current page
            redirectInput.value = window.location.href;
            console.log('[Modal] _redirect_url set to:', redirectInput.value);

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
                form.addEventListener('submit', function(e) {
                    const methodInput = form.querySelector('input[name="_method"]');
                    const isUpdate = methodInput && (methodInput.value === 'PUT' || methodInput.value === 'PATCH');

                    if (isUpdate) {
                        e.preventDefault();
                        // Use async confirmation
                        if (typeof window.showConfirm === 'function') {
                            window.showConfirm({
                                title: 'Simpan Perubahan?',
                                message: 'Apakah Anda yakin ingin menyimpan perubahan data ini?',
                                confirmText: 'Simpan',
                                cancelText: 'Batal',
                                type: 'primary'
                            }).then(confirmed => {
                                if (confirmed) {
                                    showLoadingState(form);
                                    HTMLFormElement.prototype.submit.call(form);
                                }
                            });
                        } else {
                            if (confirm('Simpan perubahan data ini?')) {
                                showLoadingState(form);
                                HTMLFormElement.prototype.submit.call(form);
                            }
                        }
                        return;
                    }

                    console.log('[Modal] Form submitting to:', form.action);
                    // Log all form data
                    const formData = new FormData(form);
                    for (let [key, value] of formData.entries()) {
                        if (key !== 'photo') { // Don't log file content
                            console.log('[Modal] Form data:', key, '=', value);
                        }
                    }
                    showLoadingState(form);
                });
            }
        } else {
            console.error('[Modal] No form found in modalBody');
        }
    }

    function showLoadingState(form) {
        // Show loading state before submit
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';
        }
    }

    // Helper function to determine material type and action from URL
    function getMaterialInfo(url) {
        let materialType = '';
        let action = '';
        let materialLabel = 'Material';

        if (url.includes('/bricks/')) {
            materialType = 'brick';
            materialLabel = 'Bata';
        } else if (url.includes('/cats/')) {
            materialType = 'cat';
            materialLabel = 'Cat';
        } else if (url.includes('/cements/')) {
            materialType = 'cement';
            materialLabel = 'Semen';
        } else if (url.includes('/nats/')) {
            materialType = 'nat';
            materialLabel = 'Nat';
        } else if (url.includes('/sands/')) {
            materialType = 'sand';
            materialLabel = 'Pasir';
        } else if (url.includes('/ceramics/')) {
            materialType = 'ceramic';
            materialLabel = 'Keramik';
        }

        if (url.includes('/create')) {
            action = 'create';
        } else if (url.includes('/edit')) {
            action = 'edit';
        } else if (url.includes('/show')) {
            action = 'show';
        }

        return { materialType, action, materialLabel };
    }

    // Helper function to load store autocomplete
    function loadStoreAutocomplete(modalBodyEl) {
        if (!window.storeAutocompleteLoaded) {
            const storeScript = document.createElement('script');
            storeScript.src = '/js/store-autocomplete.js?v=' + Date.now();
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

    // Helper function to load material-specific form script
    function loadMaterialFormScript(materialType, modalBodyEl) {
        const scriptProperty = `${materialType}FormScriptLoaded`;
        const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;
        console.log('[Modal] Loading script for:', materialType, 'Init function:', initFunctionName);

        if (!window[scriptProperty]) {
            console.log('[Modal] Script not loaded yet, loading:', `/js/${materialType}-form.js`);
            const script = document.createElement('script');
            script.src = `/js/${materialType}-form.js`;
            script.onload = () => {
                console.log('[Modal] Script loaded successfully');
                window[scriptProperty] = true;
                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        console.log('[Modal] Calling', initFunctionName);
                        try {
                            window[initFunctionName](modalBodyEl);
                            console.log('[Modal]', initFunctionName, 'completed successfully');
                        } catch (e) {
                            console.error('[Modal] Error in', initFunctionName, ':', e);
                        }
                    } else {
                        console.error('[Modal] Init function not found:', initFunctionName);
                    }
                    loadStoreAutocomplete(modalBodyEl);
                    interceptFormSubmit();
                }, 100);
            };
            script.onerror = (e) => {
                console.error('[Modal] Failed to load script:', e);
            };
            document.head.appendChild(script);
        } else {
            console.log('[Modal] Script already loaded, reusing');
            setTimeout(() => {
                if (typeof window[initFunctionName] === 'function') {
                    console.log('[Modal] Calling', initFunctionName);
                    try {
                        window[initFunctionName](modalBodyEl);
                        console.log('[Modal]', initFunctionName, 'completed successfully');
                    } catch (e) {
                        console.error('[Modal] Error in', initFunctionName, ':', e);
                    }
                } else {
                    console.error('[Modal] Init function not found:', initFunctionName);
                }
                loadStoreAutocomplete(modalBodyEl);
                interceptFormSubmit();
            }, 100);
        }
    }

    if (modal && modalBody && modalTitle && closeBtn && backdrop) {
        let isFormDirty = false;

        // Use event delegation for open-modal links to handle dynamically loaded content
        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('.open-modal');
            if (!link) return;

            e.preventDefault();
            const url = link.href;
            const { materialType, action, materialLabel } = getMaterialInfo(url);

            // Reset dirty flag immediately on open
            isFormDirty = false;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update title based on action
            if (action === 'create') {
                modalTitle.textContent = `Tambah ${materialLabel} Baru`;
                closeBtn.style.display = 'flex';
            } else if (action === 'edit') {
                modalTitle.textContent = `Edit ${materialLabel}`;
                closeBtn.style.display = 'flex';
            } else if (action === 'show') {
                modalTitle.textContent = `Detail ${materialLabel}`;
                closeBtn.style.display = 'flex';
            } else {
                modalTitle.textContent = materialLabel;
                closeBtn.style.display = 'flex';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                console.log('[Modal] Received HTML length:', html.length);
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('form') || doc.querySelector('.card') || doc.body;
                console.log('[Modal] Content found:', content ? content.tagName : 'none');

                if (content && content.tagName === 'FORM') {
                    // Check if form has required hidden inputs
                    const hasToken = content.querySelector('input[name="_token"]');
                    const hasMethod = content.querySelector('input[name="_method"]');
                    console.log('[Modal] Form has _token:', !!hasToken, '_method:', !!hasMethod);
                }

                modalBody.innerHTML = content ? content.outerHTML : html;

                // Track dirty state
                const loadedForm = modalBody.querySelector('form');
                if (loadedForm) {
                    loadedForm.addEventListener('input', () => { isFormDirty = true; });
                    loadedForm.addEventListener('change', () => { isFormDirty = true; });
                }

                // Always intercept form submit first to add redirect URL
                interceptFormSubmit();

                // Load material-specific form script if needed
                if (materialType && (action === 'create' || action === 'edit')) {
                    loadMaterialFormScript(materialType, modalBody);
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                console.error('Fetch error:', err);
            });
        });

    async function closeModal() {
        if (isFormDirty) {
            let confirmed = true;
            if (typeof window.showConfirm === 'function') {
                confirmed = await window.showConfirm({
                    title: 'Batalkan Perubahan?',
                    message: 'Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?',
                    confirmText: 'Ya, Tutup',
                    cancelText: 'Kembali',
                    type: 'warning'
                });
            } else {
                confirmed = window.confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?');
            }
            if (!confirmed) return;
        }

        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
            isFormDirty = false;
        }, 300);
    }

    // Expose closeModal as global function for form cancel buttons
    window.closeFloatingModalLocal = closeModal;
    
    // Save original global closer (from app.blade.php)
    const originalGlobalCloser = window.closeFloatingModal;
    
    window.closeFloatingModal = function() {
        // If local modal is open, use local closer (with dirty check)
        if (modal.classList.contains('active')) {
            return closeModal();
        }
        // Otherwise delegate to original global closer (which handles global modal dirty check)
        if (typeof originalGlobalCloser === 'function') {
            return originalGlobalCloser();
        }
    };

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    modalBody.addEventListener('click', function(e) {
        const cancelBtn = e.target.closest('.btn-cancel');
        if (!cancelBtn) return;
        e.preventDefault();
        closeModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    }

    // Material Choice Modal
    const materialChoiceModal = document.getElementById('materialChoiceModal');
    const openMaterialChoiceBtn = document.getElementById('openMaterialChoiceModal');
    const closeMaterialChoiceBtn = document.getElementById('closeMaterialChoiceModal');
    const materialChoiceBackdrop = materialChoiceModal ? materialChoiceModal.querySelector('.floating-modal-backdrop') : null;

    if (materialChoiceModal && openMaterialChoiceBtn && closeMaterialChoiceBtn && materialChoiceBackdrop) {
        // Open material choice modal
        openMaterialChoiceBtn.addEventListener('click', function() {
            materialChoiceModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close material choice modal
        function closeMaterialChoiceModal() {
            materialChoiceModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        closeMaterialChoiceBtn.addEventListener('click', closeMaterialChoiceModal);
        materialChoiceBackdrop.addEventListener('click', closeMaterialChoiceModal);

        // Close material choice modal on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && materialChoiceModal.classList.contains('active')) {
                closeMaterialChoiceModal();
            }
        });

        // When user clicks a material choice, close the choice modal first
        document.querySelectorAll('.material-choice-card').forEach(card => {
            card.addEventListener('click', function() {
                closeMaterialChoiceModal();
                // The open-modal class will handle opening the form modal
            });
        });
    }

    // Initialize tab click listeners
    if (tabButtons.length) {
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.tab);
                updateActivePaginationLetter();
                // Also update the stored URL when tab changes to ensure we return to this tab
                // We construct a new URL with the updated 'tab' parameter
                const url = new URL(window.location.href);
                url.searchParams.set('tab', btn.dataset.tab);
                // Reset page to 1 when switching tabs to avoid empty pages
                url.searchParams.delete(btn.dataset.tab + '_page'); 
                localStorage.setItem('lastMaterialsUrl', url.toString());
                // Note: We don't pushState here to avoid reload, but saving to LS is enough for Navbar return
            });
        });
    }

    // --- Save Current State for Navbar Return ---
    // Save the full current URL to localStorage on page load
    localStorage.setItem('lastMaterialsUrl', window.location.href);

    // Add click handlers to "Lihat Semua" buttons to save current tab
    document.querySelectorAll('a[href*="bricks.index"], a[href*="cats.index"], a[href*="cements.index"], a[href*="sands.index"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Save current active tab before navigation
            const activeTab = document.querySelector('.material-tab-btn.active');
            if (activeTab) {
                const currentTab = activeTab.dataset.tab;
                localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, currentTab);
            }
        });
    });

    function highlightMaterialRowElement(row, options = {}) {
        if (!row) return;
        const container = row.closest('.table-container');
        if (!container) return;

        const preserveExisting = !!options.preserveExisting;
        const outlineClass = options.outlineClass || 'material-row-outline';
        const removeSelector = options.removeSelector || '.material-row-outline';

        if (!preserveExisting) {
            const existing = container.querySelector(removeSelector);
            if (existing) {
                existing.remove();
            }
        }

        const containerRect = container.getBoundingClientRect();
        const rowRect = row.getBoundingClientRect();
        const outline = document.createElement('div');
        outline.className = outlineClass;
        outline.style.left = `${rowRect.left - containerRect.left + container.scrollLeft}px`;
        outline.style.top = `${rowRect.top - containerRect.top + container.scrollTop}px`;
        outline.style.width = `${rowRect.width}px`;
        outline.style.height = `${rowRect.height}px`;

        container.appendChild(outline);
        window.setTimeout(() => {
            outline.remove();
        }, 2600);
    }

    function highlightMaterialRow(targetId) {
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (!target) return;
        const row = target.closest('tr');
        if (!row) return;
        highlightMaterialRowElement(row);
    }

    function parseAnchorBrandLetter(targetId) {
        if (!targetId || typeof targetId !== 'string') return null;
        const match = targetId.match(/^(.+?)-letter-(.+)$/i);
        if (!match || !match[1] || !match[2]) return null;

        const tabType = String(match[1]).trim();
        let suffix = String(match[2]).trim().toUpperCase();
        if (!tabType || !suffix) return null;

        if (suffix === 'OTHER') {
            suffix = '#';
        }

        const letter = /^[A-Z]$/.test(suffix) ? suffix : '#';
        return { tabType, letter };
    }

    function isLetterAnchorTarget(targetId) {
        return !!parseAnchorBrandLetter(targetId);
    }

    function blinkRowsByAnchorLetter(targetId) {
        const parsed = parseAnchorBrandLetter(targetId);
        if (!parsed) return;

        const panel =
            document.querySelector(`.material-tab-panel[data-tab="${parsed.tabType}"]`) ||
            document.querySelector('.material-tab-panel.active');
        if (!panel) return;

        const rows = Array.from(panel.querySelectorAll('tbody tr')).filter(row => {
            const brandCell = row.querySelector('.material-brand-cell');
            if (!brandCell) return false;
            const brandText = String(brandCell.textContent || '').trim();
            if (!brandText) return parsed.letter === '#';
            let firstChar = brandText.charAt(0).toUpperCase();
            if (!/^[A-Z]$/.test(firstChar)) {
                firstChar = '#';
            }
            return firstChar === parsed.letter;
        });

        if (!rows.length) return;

        // Use the same visual effect as single-row skip highlight, but apply to all matching rows.
        const containers = new Set();
        rows.forEach(row => {
            const container = row.closest('.table-container');
            if (!container) return;
            containers.add(container);
        });

        containers.forEach(container => {
            container.querySelectorAll('.material-row-outline-group').forEach(el => el.remove());
        });

        rows.forEach(row => {
            highlightMaterialRowElement(row, {
                preserveExisting: true,
                outlineClass: 'material-row-outline material-row-outline-group',
                removeSelector: '.material-row-outline-group',
            });
        });
    }

    function normalizeMaterialSearchValue(value) {
        return (value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/gi, ' ')
            .trim()
            .replace(/\s+/g, ' ');
    }

    function highlightMaterialRowByType(materialTab, typeValue) {
        if (!materialTab || !typeValue) return;
        const panel = document.querySelector(`.material-tab-panel[data-tab="${materialTab}"]`);
        if (!panel) return;

        const normalized = normalizeMaterialSearchValue(typeValue);
        if (!normalized) return;

        const tokens = normalized.split(' ').filter(Boolean);
        const rows = panel.querySelectorAll('tbody tr[data-material-kind]');
        let match = null;
        rows.forEach(row => {
            if (match) return;
            const rowSearch = normalizeMaterialSearchValue(row.dataset.materialSearch || '');
            if (rowSearch && tokens.length) {
                const matchesAll = tokens.every(token => rowSearch.includes(token));
                if (matchesAll) {
                    match = row;
                    return;
                }
            }

            const rowType = normalizeMaterialSearchValue(row.dataset.materialKind || '');
            if (rowType && rowType === normalized) {
                match = row;
            }
        });

        if (!match) return;
        match.scrollIntoView({ behavior: 'smooth', block: 'center' });
        highlightMaterialRowElement(match);
    }

    let newMaterialHandled = false;
    let newMaterialFocusAttempts = 0;

    function clearPendingMaterialFocusStorage() {
        try {
            sessionStorage.removeItem('pendingMaterialFocus');
        } catch (e) {
            // Ignore storage errors
        }
    }

    function clearMaterialFocusQueryParams() {
        try {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('_focus_type') && !url.searchParams.has('_focus_id')) {
                return;
            }
            url.searchParams.delete('_focus_type');
            url.searchParams.delete('_focus_id');
            history.replaceState(null, null, url.toString());
        } catch (e) {
            // Ignore URL parsing errors
        }
    }
    let newMaterialRow = null;

    function createMaterialRowSweep(row) {
        const container = row.closest('.table-container');
        if (!container) return null;

        const existing = container.querySelector('.material-row-sweep');
        if (existing) {
            existing.remove();
        }

        const containerRect = container.getBoundingClientRect();
        const rowRect = row.getBoundingClientRect();
        const sweep = document.createElement('div');
        sweep.className = 'material-row-sweep';
        sweep.style.left = `${rowRect.left - containerRect.left + container.scrollLeft}px`;
        sweep.style.top = `${rowRect.top - containerRect.top + container.scrollTop}px`;
        sweep.style.width = `${rowRect.width}px`;
        sweep.style.height = `${rowRect.height}px`;

        container.appendChild(sweep);
        return sweep;
    }

    function clearNewMaterialHighlight() {
        if (!newMaterialRow) return;
        newMaterialRow.classList.remove('material-row-new');
        if (newMaterialRow.__materialRowSweep) {
            newMaterialRow.__materialRowSweep.remove();
            newMaterialRow.__materialRowSweep = null;
        }
        newMaterialRow = null;
    }

    function bindNewMaterialClear(row) {
        const clear = () => clearNewMaterialHighlight();
        document.addEventListener('pointerdown', clear, { once: true });
        document.addEventListener('touchstart', clear, { once: true, passive: true });
        document.addEventListener('keydown', clear, { once: true });
        document.addEventListener('wheel', clear, { once: true, passive: true });
        const container = row.closest('.table-container');
        if (container) {
            container.addEventListener('scroll', clear, { once: true, passive: true });
        }
    }

    function focusNewMaterialRow() {
        if (newMaterialHandled) return;
        if (!newMaterialData || !newMaterialData.type || !newMaterialData.id) return;

        const panel = document.querySelector(`.material-tab-panel[data-tab="${newMaterialData.type}"]`);
        if (panel) {
            setActiveTab(newMaterialData.type);
        }

        const activePanel = panel || document.querySelector(`.material-tab-panel[data-tab="${newMaterialData.type}"]`);
        let row = null;
        if (activePanel) {
            const directMatch = activePanel.querySelector(`tbody tr[data-material-id="${newMaterialData.id}"]`);
            if (directMatch) {
                row = directMatch;
            } else {
                const allRows = Array.from(activePanel.querySelectorAll('tbody tr[data-material-id]'));
                row = allRows.find(item => String(item.dataset.materialId || '') === String(newMaterialData.id)) || null;
            }
        }
        if (!row) {
            newMaterialFocusAttempts += 1;
            if (newMaterialFocusAttempts < 40) {
                window.setTimeout(focusNewMaterialRow, 220);
                return;
            }
            newMaterialHandled = true;
            return;
        }

        newMaterialHandled = true;
        clearPendingMaterialFocusStorage();
        clearMaterialFocusQueryParams();

        newMaterialRow = row;
        row.classList.add('material-row-new');
        scrollRowIntoContainer(row, 'auto');
        window.setTimeout(() => {
            if (!newMaterialRow) return;
            newMaterialRow.__materialRowSweep = createMaterialRowSweep(newMaterialRow);
        }, 260);
        window.setTimeout(() => {
            bindNewMaterialClear(row);
        }, 400);
    }

    window.deleteMaterial = async function(type, id) {
        const endpointMap = {
            brick: 'bricks',
            cat: 'cats',
            cement: 'cements',
            nat: 'nats',
            sand: 'sands',
            ceramic: 'ceramics',
        };
        const labelMap = {
            brick: 'bata',
            cat: 'cat',
            cement: 'semen',
            nat: 'nat',
            sand: 'pasir',
            ceramic: 'keramik',
        };

        const endpoint = endpointMap[type];
        const label = labelMap[type] || 'material';
        if (!endpoint) {
            const message = 'Tipe material tidak dikenal.';
            window.showToast(message, 'error');
            return;
        }

        const confirmed = await window.showConfirm({
            message: `Yakin ingin menghapus data ${label} ini?`,
            confirmText: 'Hapus',
            cancelText: 'Batal',
            type: 'danger'
        });
        if (!confirmed) return;

        try {
            const result = await api.delete(`/${endpoint}/${id}`);
            if (result.success) {
                localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, type);
                sessionStorage.setItem('pendingToast', JSON.stringify({
                    type: 'success',
                    message: `Data ${label} berhasil dihapus.`
                }));
                window.location.reload();
            } else {
                const message = 'Gagal menghapus data: ' + (result.message || 'Terjadi kesalahan');
                window.showToast(message, 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            const message = 'Gagal menghapus data. Silakan coba lagi.';
            window.showToast(message, 'error');
        }
    };

    function blinkPaginationLetter(linkEl) {
        if (!linkEl) return;
        linkEl.classList.remove('material-letter-blink');
        void linkEl.offsetWidth;
        linkEl.classList.add('material-letter-blink');
        window.setTimeout(() => {
            linkEl.classList.remove('material-letter-blink');
        }, 2000);
    }

    // Helper function to update active pagination letter
    function updateActivePaginationLetter(forcedHash = null) {
        const explicitHash =
            typeof forcedHash === 'string' && forcedHash.startsWith('#')
                ? forcedHash
                : '';

        // Remove 'current' class from all pagination links
        document.querySelectorAll('.kanggo-img-link').forEach(link => {
            link.classList.remove('current');
        });

        // Default: mark first available letter in the active tab
        const activePanel = document.querySelector('.material-tab-panel.active') || document.querySelector('.material-tab-panel');
        if (!activePanel) return;
        const activeButton = document.querySelector('.material-tab-btn.active');
        const activeTabType =
            activePanel.getAttribute('data-tab')
            || (activeButton ? activeButton.getAttribute('data-tab') : '')
            || '';

        const activateByHash = candidateHash => {
            if (!candidateHash) return false;
            const matchingLink = activePanel.querySelector(`.kanggo-img-link[href="${candidateHash}"]`);
            if (matchingLink) {
                matchingLink.classList.add('current');
                return true;
            }
            return false;
        };

        // 1) If hash is explicitly passed, prioritize it.
        if (explicitHash && activateByHash(explicitHash)) {
            rememberLetterForTab(activeTabType, explicitHash);
            return;
        }

        // 2) Restore remembered letter for this tab if available.
        const rememberedHash = getRememberedLetterForTab(activeTabType);
        if (rememberedHash && activateByHash(rememberedHash)) {
            const rememberedLink = activePanel.querySelector(`.kanggo-img-link[href="${rememberedHash}"]`);
            if (rememberedLink) {
                blinkPaginationLetter(rememberedLink);
            }
            return;
        }

        // 3) In skip-page mode, keep default grey when no explicit/remembered letter.
        if (window.__materialSkipPage) {
            return;
        }

        // 4) Normal fallback: first letter in active tab.
        const firstLink = activePanel.querySelector('.kanggo-img-link');
        if (firstLink) {
            firstLink.classList.add('current');
        }
    }

    // Helper function to scroll container to target element with offset
    function scrollToTargetInContainer(targetId) {
        const targetElement = document.getElementById(targetId);
        if (!targetElement) return;

        // Find the scrollable container (.table-container)
        const container = targetElement.closest('.table-container');
        if (!container) return;

        // Calculate offset: position of target relative to container top
        const containerRect = container.getBoundingClientRect();
        const targetRect = targetElement.getBoundingClientRect();
        const relativeTop = targetRect.top - containerRect.top;
        const currentScroll = container.scrollTop;

        const header = container.querySelector('thead');
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const offset = headerHeight + 12;
        const scrollTarget = currentScroll + relativeTop - offset;

        // Smooth scroll the container
        container.scrollTo({
            top: scrollTarget,
            behavior: 'smooth'
        });
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightSearchMatchesInCell(cell, queryLower, escapedQuery) {
        if (!cell || !queryLower) return false;
        const cellText = cell.textContent || '';
        
        // Check if query is numeric (ignoring spaces)
        const isNumericQuery = /^[0-9.,]+$/.test(queryLower.replace(/\s/g, ''));
        
        // Standard text search for non-numeric queries
        if (!isNumericQuery) {
            if (!cellText.toLowerCase().includes(queryLower)) return false;
        } else {
            // For numeric queries, strip formatting from cell text for comparison
            // But keep track of original indices to highlight correct range
            const normalizedCellText = cellText.replace(/[^0-9]/g, '');
            const normalizedQuery = queryLower.replace(/[^0-9]/g, '');
            
            if (!normalizedCellText.includes(normalizedQuery)) return false;
        }

        const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, {
            acceptNode: node => {
                if (!node.nodeValue || !node.nodeValue.trim()) {
                    return NodeFilter.FILTER_REJECT;
                }
                const parent = node.parentElement;
                if (parent && parent.classList.contains('material-search-hit')) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });

        const nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        let matched = false;
        nodes.forEach(node => {
            const text = node.nodeValue || '';
            let fragment = document.createDocumentFragment();
            let lastIndex = 0;
            let nodeHasMatch = false;

            if (isNumericQuery) {
                // Numeric logic: match normalized digits but highlight formatted string
                const normalizedQuery = queryLower.replace(/[^0-9]/g, '');
                
                // Build a map of normalized indices to original indices
                // "15.000" -> normalized "15000"
                // Map: 0->0('1'), 1->1('5'), 2->3('0'), 3->4('0'), 4->5('0')
                const map = [];
                let normIdx = 0;
                for (let i = 0; i < text.length; i++) {
                    if (/[0-9]/.test(text[i])) {
                        map.push(i);
                    }
                }
                
                const normalizedText = text.replace(/[^0-9]/g, '');
                let searchStart = 0;
                let matchIdx;
                
                while ((matchIdx = normalizedText.indexOf(normalizedQuery, searchStart)) !== -1) {
                    nodeHasMatch = true;
                    
                    // Map back to original string indices
                    // Start index in original string
                    const originalStart = map[matchIdx];
                    // End index in original string (inclusive of last char)
                    const originalEnd = map[matchIdx + normalizedQuery.length - 1] + 1;
                    
                    if (originalStart > lastIndex) {
                        fragment.appendChild(document.createTextNode(text.slice(lastIndex, originalStart)));
                    }
                    
                    const span = document.createElement('span');
                    span.className = 'material-search-hit';
                    span.textContent = text.slice(originalStart, originalEnd);
                    fragment.appendChild(span);
                    
                    lastIndex = originalEnd;
                    searchStart = matchIdx + normalizedQuery.length;
                }
            } else {
                // Standard text search
                const regex = new RegExp(escapedQuery, 'gi');
                let match = null;
                while ((match = regex.exec(text)) !== null) {
                    nodeHasMatch = true;
                    const start = match.index;
                    if (start > lastIndex) {
                        fragment.appendChild(document.createTextNode(text.slice(lastIndex, start)));
                    }
                    const span = document.createElement('span');
                    span.className = 'material-search-hit';
                    span.textContent = text.slice(start, start + match[0].length);
                    fragment.appendChild(span);
                    lastIndex = start + match[0].length;
                }
            }

            if (!nodeHasMatch) return;
            if (lastIndex < text.length) {
                fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
            }
            node.parentNode.replaceChild(fragment, node);
            matched = true;
        });

        return matched;
    }

    function highlightSearchMatches() {
        if (!hasSearchQuery) return;
        const escapedQuery = escapeRegExp(searchQueryRaw);
        if (!escapedQuery) return;

        document.querySelectorAll('.material-tab-panel table').forEach(table => {
            const tbody = table.tBodies[0];
            if (!tbody) return;
            Array.from(tbody.rows).forEach(row => {
                Array.from(row.cells).forEach(cell => {
                    if (cell.cellIndex === 0 || cell.classList.contains('action-cell')) return;
                    highlightSearchMatchesInCell(cell, normalizedSearchQuery, escapedQuery);
                });
            });
        });
    }

    function updateRowNumbers(tbody) {
        if (!tbody) return;
        Array.from(tbody.rows).forEach((row, index) => {
            const firstCell = row.cells[0];
            if (firstCell) {
                firstCell.textContent = index + 1;
            }
        });
    }

    function sortRowsBySearchMatch(table, queryLower) {
        if (!table || !queryLower) return;
        const tbody = table.tBodies[0];
        if (!tbody) return;
        const rows = Array.from(tbody.rows);
        const rankedRows = rows.map((row, index) => {
            let firstMatchIndex = Number.POSITIVE_INFINITY;
            const cells = Array.from(row.cells);
            for (let i = 1; i < cells.length; i += 1) {
                const cell = cells[i];
                if (!cell || cell.classList.contains('action-cell')) continue;
                const text = (cell.textContent || '').toLowerCase();
                if (text.includes(queryLower)) {
                    firstMatchIndex = i;
                    break;
                }
            }
            return { row, index, firstMatchIndex };
        });

        rankedRows.sort((a, b) => {
            if (a.firstMatchIndex === b.firstMatchIndex) {
                return a.index - b.index;
            }
            return a.firstMatchIndex - b.firstMatchIndex;
        });

        const fragment = document.createDocumentFragment();
        rankedRows.forEach(item => fragment.appendChild(item.row));
        tbody.appendChild(fragment);
        updateRowNumbers(tbody);
    }

    function buildHeaderIndexMap(table) {
        const header = table.tHead;
        if (!header) return;
        const rows = Array.from(header.rows);
        const occupied = [];

        rows.forEach((row, rowIndex) => {
            if (!occupied[rowIndex]) {
                occupied[rowIndex] = [];
            }
            let colIndex = 0;
            Array.from(row.cells).forEach(cell => {
                while (occupied[rowIndex][colIndex]) {
                    colIndex += 1;
                }
                const colspan = cell.colSpan || 1;
                const rowspan = cell.rowSpan || 1;
                cell.dataset.colStart = colIndex;
                cell.dataset.colEnd = colIndex + colspan - 1;
                for (let r = 0; r < rowspan; r += 1) {
                    if (!occupied[rowIndex + r]) {
                        occupied[rowIndex + r] = [];
                    }
                    for (let c = 0; c < colspan; c += 1) {
                        occupied[rowIndex + r][colIndex + c] = true;
                    }
                }
                colIndex += colspan;
            });
        });
    }

    function scrollRowIntoContainer(row, behavior = 'smooth') {
        if (!row) return;
        const container = row.closest('.table-container');
        if (!container) return;

        const containerRect = container.getBoundingClientRect();
        const rowRect = row.getBoundingClientRect();
        const header = container.querySelector('thead');
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const offset = headerHeight + 12;
        const scrollTarget = container.scrollTop + (rowRect.top - containerRect.top) - offset;

        container.scrollTo({
            top: scrollTarget,
            behavior: behavior
        });
    }

    function findMatchingRowForColumns(table, queryLower, colStart, colEnd) {
        if (!table || !queryLower) return null;
        const tbody = table.tBodies[0];
        if (!tbody) return null;
        const rows = Array.from(tbody.rows);
        for (const row of rows) {
            const cells = Array.from(row.cells);
            for (let colIndex = colStart; colIndex <= colEnd; colIndex += 1) {
                const cell = cells[colIndex];
                if (!cell) continue;
                const text = (cell.textContent || '').toLowerCase();
                if (text.includes(queryLower)) {
                    return row;
                }
            }
        }
        return null;
    }

    function isSearchableColumnRange(table, colStart, colEnd) {
        const tbody = table.tBodies[0];
        if (!tbody || !tbody.rows.length) return false;
        const sampleCells = Array.from(tbody.rows[0].cells);
        for (let colIndex = colStart; colIndex <= colEnd; colIndex += 1) {
            const cell = sampleCells[colIndex];
            if (!cell) continue;
            if (colIndex === 0) continue;
            if (cell.classList.contains('action-cell')) continue;
            return true;
        }
        return false;
    }

    function showSearchJumpToast(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'info');
        }
    }

    function bindHeaderSearchJump(table, queryLower) {
        if (!table || !queryLower) return;
        const headerCells = table.querySelectorAll('thead th');
        headerCells.forEach(th => {
            if (th.dataset.searchJumpBound === 'true') return;
            const colStart = Number.parseInt(th.dataset.colStart, 10);
            const colEnd = Number.parseInt(th.dataset.colEnd, 10);
            if (Number.isNaN(colStart) || Number.isNaN(colEnd)) return;
            if (!isSearchableColumnRange(table, colStart, colEnd)) return;
            
            // Find the sort link if it exists
            const sortLink = th.querySelector('a[href*="sort_by"]');
            const targetElement = sortLink || th;
            const headerLabel = (th.textContent || '').replace(/\s+/g, ' ').trim();

            th.classList.add('material-search-jump');
            
            // Attach click listener
            targetElement.addEventListener('click', event => {
                // If clicked on sort icon, allow default behavior (sorting)
                if (event.target.closest('.bi-sort-down') || event.target.closest('.bi-sort-up-alt') || event.target.closest('.bi-arrow-down-up')) {
                    return;
                }

                // If clicked on text or cell background, prevent sort and jump to search result
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                
                event.preventDefault();
                event.stopPropagation(); // Stop propagation to prevent link navigation

                const matchRow = findMatchingRowForColumns(table, queryLower, colStart, colEnd);
                if (!matchRow) {
                    const labelText = headerLabel ? ` di kolom ${headerLabel}` : '';
                    showSearchJumpToast(`Tidak ada hasil pencarian${labelText}.`);
                    return;
                }
                scrollRowIntoContainer(matchRow);
                window.setTimeout(() => highlightMaterialRowElement(matchRow), 250);
            });
            
            th.dataset.searchJumpBound = 'true';
        });
    }

    function setupSearchEnhancements() {
        if (!hasSearchQuery) return;
        document.querySelectorAll('.material-tab-panel table').forEach(table => {
            sortRowsBySearchMatch(table, normalizedSearchQuery);
            buildHeaderIndexMap(table);
            bindHeaderSearchJump(table, normalizedSearchQuery);
        });
        highlightSearchMatches();
    }

    function applyAllStickyOffsets() {
        // Function to apply sticky offsets to a specific section and class
        const applyToSection = (sectionId, stickyClass) => {
            const panel = document.getElementById(sectionId);
            if (!panel || panel.offsetParent === null) return;
            const tables = panel.querySelectorAll('table');
            tables.forEach(table => {
                // To support both single-row body cells and multi-row headers,
                // we iterate over all rows (thead and tbody)
                const rows = table.querySelectorAll('tr');
                rows.forEach(row => {
                    const stickyCells = row.querySelectorAll('.' + stickyClass);
                    stickyCells.forEach(cell => {
                        // Reset left to let browser calculate natural position
                        cell.style.left = '';
                        // Set left to the calculated offset
                        // This ensures consistent width even when sticky
                        cell.style.left = `${cell.offsetLeft}px`;
                    });
                });
            });
        };

        // Apply to Ceramic (existing)
        applyToSection('section-ceramic', 'ceramic-sticky-col');
        
        // Apply to Cat (new)
        applyToSection('section-cat', 'cat-sticky-col');
        
        // Apply to Cement (new)
        applyToSection('section-cement', 'cement-sticky-col');
        applyToSection('section-nat', 'cement-sticky-col');
    }

    // Add click handlers to pagination links to preserve current tab
    document.querySelectorAll('.kanggo-img-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor behavior
            e.stopPropagation();

            // Save current active tab before navigation
            const activeTab = document.querySelector('.material-tab-btn.active');
            if (activeTab) {
                const currentTab = activeTab.dataset.tab;
                localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, currentTab);
            }

            const href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                const url = new URL(window.location.href);
                const sortBy = url.searchParams.get('sort_by');
                const sortDirection = url.searchParams.get('sort_direction');
                if (sortBy !== 'brand' || sortDirection !== 'asc') {
                    url.searchParams.set('sort_by', 'brand');
                    url.searchParams.set('sort_direction', 'asc');
                    url.hash = href;
                    window.location.href = url.toString();
                    return;
                }
                const targetId = href.slice(1);
                const activeTabType = activeTab && activeTab.dataset ? activeTab.dataset.tab : '';
                rememberLetterForTab(activeTabType, href);

                // Keep hash in JS state only to avoid native page jump.
                window.__materialHash = href;
                link.blur();

                // Update active pagination letter
                updateActivePaginationLetter(href);

                // Scroll to target in container
                scrollToTargetInContainer(targetId);

                // Highlight/blink after scroll
                window.setTimeout(() => {
                    if (isLetterAnchorTarget(targetId)) {
                        blinkRowsByAnchorLetter(targetId);
                    } else {
                        highlightMaterialRow(targetId);
                    }
                }, 400);

            }
        });
    });

    // Handle page load with hash (prevent native jump)
    const initialHash = window.__materialHash || window.location.hash;
    if (initialHash) {
        if (initialHash !== '#skip-page') {
            // Keep window at current position (prevent scroll to anchor)
            const savedScrollY = window.__savedScrollY || 0;
            window.scrollTo(0, savedScrollY);

            window.setTimeout(() => {
                const targetId = initialHash.slice(1);
                // Update active pagination letter
                updateActivePaginationLetter(initialHash);
                // Scroll to target in container (NOT window scroll)
                scrollToTargetInContainer(targetId);
                // Highlight/blink row
                window.setTimeout(() => {
                    if (isLetterAnchorTarget(targetId)) {
                        blinkRowsByAnchorLetter(targetId);
                    } else {
                        highlightMaterialRow(targetId);
                    }
                }, 400);
            }, 500);

            // Restore smooth scroll behavior
            window.setTimeout(() => {
                document.documentElement.style.scrollBehavior = '';
                document.documentElement.style.scrollPaddingTop = '';
            }, 600);
        }
    }

    // Handle hash change events
    window.addEventListener('hashchange', () => {
        const targetId = window.location.hash.slice(1);

        // Update active pagination letter
        updateActivePaginationLetter(window.location.hash);

        // Scroll to target
        scrollToTargetInContainer(targetId);

        // Highlight/blink row
        window.setTimeout(() => {
            if (isLetterAnchorTarget(targetId)) {
                blinkRowsByAnchorLetter(targetId);
            } else {
                highlightMaterialRow(targetId);
            }
        }, 400);
    });

    // Initial update of active pagination letter on page load
    if (wasSkipPageOnLoad) {
        const activeTabBtn = document.querySelector('.material-tab-btn.active');
        const activeTabType = activeTabBtn ? activeTabBtn.getAttribute('data-tab') : '';
        const rememberedHash = getRememberedLetterForTab(activeTabType);
        if (rememberedHash) {
            updateActivePaginationLetter(rememberedHash);
            window.setTimeout(() => {
                blinkRowsByAnchorLetter(rememberedHash.replace(/^#/, ''));
            }, 320);
        }
    } else {
        updateActivePaginationLetter();
    }
    setupSearchEnhancements();
    
    // Apply sticky offsets for all relevant sections
    applyAllStickyOffsets();
    
    window.setTimeout(() => {
        focusNewMaterialRow();
    }, 200);

    window.addEventListener('resize', () => {
        window.requestAnimationFrame(() => {
            applyAllStickyOffsets();
        });
    });

    // requestStickyUpdate(); // Removed - sticky footer functionality disabled
    // window.addEventListener('scroll', requestStickyUpdate, { passive: true });
    // window.addEventListener('resize', requestStickyUpdate);

});
</script>
@endpush
