@extends('layouts.app')

@section('title', 'Semua Material')

@section('content')
<!-- Inline script to restore tab ASAP before page render -->
<script>
(function() {
    document.documentElement.classList.add('materials-booting');
    document.documentElement.classList.add('materials-lock');
})();
(function() {
    const savedTab = localStorage.getItem('materialActiveTab');
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
        // Always strip hash from URL immediately
        history.replaceState(null, null, window.location.pathname + window.location.search);
        
        if (h === '#skip-page') {
            // Aggressively force top for #skip-page and do NOT pass to main logic
            document.documentElement.style.scrollBehavior = 'auto';
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            return;
        }
        
        window.__materialHash = h;
    }
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
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
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
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
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
  #section-ceramic .table-container thead {
      height: 40px !important;
  }

  /* Single header rows should fill the full 40px - COMPACT */
  #section-cat .table-container thead.single-header tr th,
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
  #section-sand .sand-scroll-td,
  #section-cat .cat-scroll-td,
  #section-brick .brick-scroll-td {
      position: relative;
      overflow: hidden;
  }
  #section-ceramic .ceramic-scroll-td.is-scrollable::after,
  #section-cement .cement-scroll-td.is-scrollable::after,
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
  #section-sand .sand-scroll-td.is-scrolled-end::after,
  #section-cat .cat-scroll-td.is-scrolled-end::after,
  #section-brick .brick-scroll-td.is-scrolled-end::after {
      opacity: 0;
  }
  #section-ceramic .ceramic-scroll-cell,
  #section-cement .cement-scroll-cell,
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
    background-color: rgba(37, 99, 235, 0.08);
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
#section-cement thead .cement-sticky-col {
    z-index: 7;
}
#section-cement .cement-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}


.material-search-hit {
    color: #2563eb;
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
      color: var(--special-text-color) !important;
      -webkit-text-stroke: var(--special-text-stroke) !important;
      font-weight: var(--special-font-weight) !important;
      text-shadow: var(--special-text-shadow) !important;
  }
  .material-footer-label {
      color: var(--text-color) !important;
      font-weight: var(--special-font-weight) !important;
  }
  .material-footer-left {
      display: flex;
      align-items: center;
      gap: 6px;
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
      font-size: 16px !important;
  }
  .material-footer-label {
      font-size: 10px;
      line-height: 1.2;
      margin-top: 2px;
      text-align: center;
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
      height: 48px !important;
  }
  .material-footer-sticky .kanggo-letters {
      height: 50px !important;
  }
  .material-footer-sticky .kanggo-img-link img {
      height: 17px !important;
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
}
.material-tab-header .material-settings-menu {
    left: 0;
    right: auto;
    width: 100%;
    max-width: 400px;
}
.material-tab-header .material-settings-dropdown {
    position: relative;
    flex: 1 1 auto;
    align-items: flex-end;
    justify-content: flex-start;
}
.material-tab-header .material-settings-btn.active::before {
    content: none !important;
}
.material-tabs.only-filter .material-settings-btn.active::before {
    content: none;
}
.material-tab-actions {
    flex: 0 0 33%;
    max-width: 33%;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    margin-left: auto;
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
    z-index: 6;
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
}
.material-tab-btn.active {
    --tab-border-color: #91C6BC;
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
                    </button>
                @endforeach

                <div class="material-settings-dropdown">
                    <button type="button" id="materialSettingsToggle" class="material-settings-btn">
                        <i class="bi bi-sliders"></i>
                        <span>Filter</span>
                    </button>
                    <div class="material-settings-menu" id="materialSettingsMenu">
                        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(0, 0, 0, 0.05);">
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
                        <div class="nav-material-actions" style="border-top: 1px solid rgba(0, 0, 0, 0.05); margin-top: 0;">
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
                    
                    @if($material['data']->count() > 0)
                <div class="table-container text-nowrap">
                    <table>
                        <thead class="{{ in_array($material['type'], ['brick','sand','ceramic','cement','cat']) ? 'has-dim-sub' : 'single-header' }}">
                            @php                                   
                              if (!function_exists('getMaterialSortUrl')) {
                                    function getMaterialSortUrl($column, $currentSortBy, $currentDirection) {
                                        $params = array_merge(request()->query(), []);
                                        unset($params['sort_by'], $params['sort_direction']);
                                        if ($currentSortBy === $column) {
                                            if ($currentDirection === 'asc') {
                                                $params['sort_by'] = $column;
                                                $params['sort_direction'] = 'desc';
                                            } elseif ($currentDirection === 'desc') {
                                                unset($params['sort_by'], $params['sort_direction']);
                                            } else {
                                                $params['sort_by'] = $column;
                                                $params['sort_direction'] = 'asc';
                                            }
                                        } else {
                                            $params['sort_by'] = $column;
                                            $params['sort_direction'] = 'asc';
                                        }
                                        return route('materials.index', $params);
                                    }
                                }
                                $brickSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'form' => 'Bentuk',
                                    'dimension_length' => 'Dimensi ( cm )',
                                    'package_volume' => 'Volume',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'price_per_piece' => 'Harga Beli',
                                    'comparison_price_per_m3' => 'Harga <br> Komparasi ( / M3 )',
                                ];
                                $sandSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'package_unit' => 'Kemasan',
                                    'dimension_length' => 'Dimensi Kemasan ( M )',
                                    'package_volume' => 'Volume',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'package_price' => 'Harga Beli',
                                    'comparison_price_per_m3' => 'Harga <br> Komparasi ( / M3 )',
                                ];
                                $catSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'color_code' => 'Kode',
                                    'color_name' => 'Warna',
                                    'package_unit' => 'Kemasan',
                                    'volume' => 'Volume',
                                    'package_weight_net' => 'Berat Bersih',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'purchase_price' => 'Harga Beli',
                                    'comparison_price_per_kg' => 'Harga <br> Komparasi ( / Kg )',
                                ];
                                $cementSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'code' => 'Kode',
                                    'color' => 'Warna',
                                    'package_unit' => 'Kemasan',
                                    'dimension_length' => 'Dimensi ( cm )',
                                    'package_weight_net' => 'Berat Bersih',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'package_price' => 'Harga Beli',
                                    'comparison_price_per_kg' => 'Harga <br> Komparasi ( / Kg )',
                                ];
                                $ceramicSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'code' => 'Kode',
                                    'color' => 'Warna',
                                    'form' => 'Bentuk',
                                    'surface' => 'Permukaan',
                                    'packaging' => 'Kemasan',
                                    'pieces_per_package' => 'Volume',
                                    'coverage_per_package' => 'Luas ( M2 / Dus )',
                                    'dimension_length' => 'Dimensi ( cm )',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'price_per_package' => 'Harga / Kemasan',
                                    'comparison_price_per_m2' => 'Harga Komparasi <br> ( / M2 )',
                                ];
                                @endphp
                                    @if($material['type'] == 'brick')
                                        <tr class="dim-group-row">
                                            <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $brickSortable['type'] }}</span>
                                                    @if(request('sort_by') == 'type')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $brickSortable['brand'] }}</span>
                                                    @if(request('sort_by') == 'brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $brickSortable['form'] }}</span>
                                                    @if(request('sort_by') == 'form')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                                <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Dimensi ( cm )</span>
                                                    @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Volume</span>
                                                    @if(request('sort_by') == 'package_volume')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $brickSortable['store'] }}</span>
                                                    @if(request('sort_by') == 'store')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $brickSortable['address'] }}</span>
                                                    @if(request('sort_by') == 'address')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('price_per_piece', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Beli</span>
                                                    @if(request('sort_by') == 'price_per_piece')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Komparasi</span>
                                                    @if(request('sort_by') == 'comparison_price_per_m3')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th rowspan="2" class="action-cell">Aksi</th>
                                        </tr>
                                        <tr class="dim-sub-row">
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                        </tr>

                                    @elseif($material['type'] == 'sand')
                                        <tr class="dim-group-row">
                                            <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $sandSortable['type'] }}</span>
                                                    @if(request('sort_by') == 'type')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $sandSortable['brand'] }}</span>
                                                    @if(request('sort_by') == 'brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $sandSortable['package_unit'] }}</span>
                                                    @if(request('sort_by') == 'package_unit')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                                <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Dimensi ( cm )</span>
                                                    @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Volume</span>
                                                    @if(request('sort_by') == 'package_volume')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $sandSortable['store'] }}</span>
                                                    @if(request('sort_by') == 'store')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $sandSortable['address'] }}</span>
                                                    @if(request('sort_by') == 'address')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Beli</span>
                                                    @if(request('sort_by') == 'package_price')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Komparasi</span>
                                                    @if(request('sort_by') == 'comparison_price_per_m3')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th rowspan="2" class="action-cell">Aksi</th>
                                        </tr>
                                        <tr class="dim-sub-row">
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                        </tr>

                                    @elseif($material['type'] == 'cat')
                                        <tr class="dim-group-row">
                                            <th class="cat-sticky-col col-no" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                            <th class="sortable cat-sticky-col col-type" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['type'] }}</span>
                                                    @if(request('sort_by') == 'type')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['brand'] }}</span>
                                                    @if(request('sort_by') == 'brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" style="text-align: start;">
                                                <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['sub_brand'] }}</span>
                                                    @if(request('sort_by') == 'sub_brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" style="text-align: right;">
                                                <a href="{{ getMaterialSortUrl('color_code', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['color_code'] }}</span>
                                                    @if(request('sort_by') == 'color_code')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('color_name', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['color_name'] }}</span>
                                                    @if(request('sort_by') == 'color_name')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['package_unit'] }}</span>
                                                    @if(request('sort_by') == 'package_unit')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('volume', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['volume'] }}</span>
                                                    @if(request('sort_by') == 'volume')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Berat<br>Bersih</span>
                                                    @if(request('sort_by') == 'package_weight_net')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" style="text-align: left; width: 150px; min-width: 150px;">
                                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['store'] }}</span>
                                                    @if(request('sort_by') == 'store')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" style="text-align: left; width: 200px; min-width: 200px;">
                                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $catSortable['address'] }}</span>
                                                    @if(request('sort_by') == 'address')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('purchase_price', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Beli</span>
                                                    @if(request('sort_by') == 'purchase_price')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Komparasi</span>
                                                    @if(request('sort_by') == 'comparison_price_per_kg')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="action-cell">Aksi</th>
                                        </tr>
                                        
                                    @elseif($material['type'] == 'cement')
                                        <tr class="dim-group-row">
                                            <th class="cement-sticky-col" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                            <th class="sortable cement-sticky-col" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['type'] }}</span>
                                                    @if(request('sort_by') == 'type')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable cement-sticky-col cement-sticky-edge" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['brand'] }}</span>
                                                    @if(request('sort_by') == 'brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['sub_brand'] }}</span>
                                                    @if(request('sort_by') == 'sub_brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: right;">
                                                <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['code'] }}</span>
                                                    @if(request('sort_by') == 'code')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['color'] }}</span>
                                                    @if(request('sort_by') == 'color')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['package_unit'] }}</span>
                                                    @if(request('sort_by') == 'package_unit')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Berat<br>Bersih</span>
                                                    @if(request('sort_by') == 'package_weight_net')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 15s0px;">
                                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['store'] }}</span>
                                                    @if(request('sort_by') == 'store')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $cementSortable['address'] }}</span>
                                                    @if(request('sort_by') == 'address')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Beli</span>
                                                    @if(request('sort_by') == 'package_price')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Komparasi</span>
                                                    @if(request('sort_by') == 'comparison_price_per_kg')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th rowspan="2" class="action-cell">Aksi</th>
                                        </tr>

                                    @elseif($material['type'] == 'ceramic')
                                    <tr class="dim-group-row">
                                        <th class="ceramic-sticky-col col-no" rowspan="2" style="text-align: center;">No</th>
                                        <th class="sortable ceramic-sticky-col col-type" rowspan="2" style="text-align: left;">
                                            <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['type'] }}</span>
                                                @if(request('sort_by') == 'type')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable ceramic-sticky-col col-dim-group" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Dimensi ( cm )</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_thickness']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable ceramic-sticky-col col-brand ceramic-sticky-edge" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['brand'] }}</span>
                                                @if(request('sort_by') == 'brand')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: left;">
                                            <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['sub_brand'] }}</span>
                                                @if(request('sort_by') == 'sub_brand')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: left;">
                                            <a href="{{ getMaterialSortUrl('surface', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['surface'] }}</span>
                                                @if(request('sort_by') == 'surface')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 12px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: right;">
                                            <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Nomor Seri<br>( Kode Pembakaran )</span>
                                                @if(request('sort_by') == 'code')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: left;">
                                            <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Corak ( {{ $ceramicSortable['color'] }} )</span>
                                                @if(request('sort_by') == 'color')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['form'] }}</span>
                                                @if(request('sort_by') == 'form')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('packaging', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['packaging'] }}</span>
                                                @if(request('sort_by') == 'packaging')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('coverage_per_package', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Luas<br>( / Dus )</span>
                                                @if(request('sort_by') == 'coverage_per_package')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                            <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['store'] }}</span>
                                                @if(request('sort_by') == 'store')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                            <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['address'] }}</span>
                                                @if(request('sort_by') == 'address')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('price_per_package', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Harga Beli</span>
                                                @if(request('sort_by') == 'price_per_package')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                            <a href="{{ getMaterialSortUrl('comparison_price_per_m2', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Harga Komparasi</span>
                                                @if(request('sort_by') == 'comparison_price_per_m2')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th rowspan="2" class="action-cell">Aksi</th>
                                    </tr>
                                    <tr class="dim-sub-row">
                                        <th class="ceramic-sticky-col col-dim-p" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                        <th class="ceramic-sticky-col col-dim-l" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                        <th class="ceramic-sticky-col col-dim-t" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                    </tr>
                                @endif
                            </thead>
                            @php
                                $letterGroups = $material['data']->groupBy(function ($item) use ($material) {
                                    // Modified to group by brand for all materials as requested
                                    $groupValue = $item->brand ?? '';
                                    $groupValue = trim((string) $groupValue);
                                    return $groupValue !== '' ? strtoupper(substr($groupValue, 0, 1)) : '#';
                                });
                                $orderedGroups = collect();
                                $isSorting = request()->filled('sort_by');
                                $defaultSort = false;
                                
                                if ($isSorting) {
                                    $orderedGroups['*'] = $material['data'];
                                } else {
                                    // Default: Sort by Type -> Brand -> etc, and FLATTEN the list (no brand grouping)
                                    $defaultSort = true;
                                    $sortedData = $material['data']->sortBy([
                                        ['type', 'asc'],
                                        ['brand', 'asc'],
                                        ['sub_brand', 'asc'],
                                        ['code', 'asc'],
                                        ['color_name', 'asc'],
                                        ['color', 'asc'],
                                    ]);
                                    $orderedGroups['*'] = $sortedData;
                                }
                                $rowNumber = 1;
                            @endphp
                            <tbody>
                                @foreach($orderedGroups as $letter => $items)
                                    @php
                                        // If default sort or explicit sort, disable group anchors
                                        $anchorId = ($isSorting || $defaultSort) ? null : ($letter === '#' ? 'other' : $letter);
                                    @endphp
                                    @foreach($items as $item)
                                        @php
                                            $rowAnchorId = (!$isSorting && !$defaultSort && $loop->first) ? $material['type'] . '-letter-' . $anchorId : null;
                                            $searchParts = array_filter([
                                                $item->type ?? null,
                                                $item->material_name ?? null,
                                                $item->cat_name ?? null,
                                                $item->cement_name ?? null,
                                                $item->sand_name ?? null,
                                                $item->brand ?? null,
                                                $item->sub_brand ?? null,
                                                $item->code ?? null,
                                                $item->color ?? null,
                                                $item->color_name ?? null,
                                                $item->form ?? null,
                                                $item->surface ?? null,
                                            ], function ($value) {
                                                return !is_null($value) && trim((string) $value) !== '';
                                            });
                                            $searchValue = strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $searchParts))));
                                            
                                            $stickyClass = '';
                                            if($material['type'] == 'ceramic') $stickyClass = 'ceramic-sticky-col col-no';
                                            elseif($material['type'] == 'cat') $stickyClass = 'cat-sticky-col col-no';
                                            elseif($material['type'] == 'cement') $stickyClass = 'cement-sticky-col';
                                        @endphp
                                <tr data-material-tab="{{ $material['type'] }}" data-material-id="{{ $item->id }}" data-material-kind="{{ $item->type ?? '' }}" data-material-search="{{ $searchValue }}">
                                    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif @if($material['type'] == 'ceramic') style="text-align: center;" @elseif($material['type'] == 'cement') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'sand') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'cat') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'brick') style="text-align: center; width: 40px; min-width: 40px;" @endif>
                                        {{ $rowNumber++ }}
                                    </td>
                                     @if($material['type'] == 'brick')
                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                        <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
                                        <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                @format($item->dimension_length)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                @format($item->dimension_width)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                @format($item->dimension_height)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 80px; min-width: 80px; font-size: 12px;">
                                            @if($item->package_volume)
                                                @format($item->package_volume)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">M3</td>
                                        <td class="brick-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
                                            <div class="brick-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
                                        </td>
                                        <td class="brick-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
                                            <div class="brick-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                            @if($item->price_per_piece)
                                                @price($item->price_per_piece)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 60px; min-width: 60px;">/ Bh</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->comparison_price_per_m3)
                                                @price($item->comparison_price_per_m3)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

                                    @elseif($material['type'] == 'cat')
                                        <td class="cat-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                        <td class="cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                        <td style="text-align: start;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="text-align: right; font-size: 12px;">{{ $item->color_code ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->color_name ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
                                            @if($item->package_weight_gross)
                                                (  @format($item->package_weight_gross )
                                            @else
                                                <span>(  -</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 50px; min-width: 50px;">Kg  )</td>
                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                            @if($item->volume)
                                                @format($item->volume)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">L</td>
                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                @format($item->package_weight_net)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">Kg</td>
                                        <td class="cat-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
                                            <div class="cat-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
                                        </td>
                                        <td class="cat-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
                                            <div class="cat-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->purchase_price)
                                                @price($item->purchase_price)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->comparison_price_per_kg)
                                                @price($item->comparison_price_per_kg)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

                                    @elseif($material['type'] == 'cement')
                                        <td class="cement-sticky-col" style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                        <td class="cement-sticky-col cement-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
                                        <td style="text-align: center; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 12px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                @format($item->package_weight_net)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">Kg</td>
                                        <td class="cement-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
                                            <div class="cement-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
                                        </td>
                                        <td class="cement-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
                                            <div class="cement-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->package_price)
                                                @price($item->package_price)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->comparison_price_per_kg)
                                                @price($item->comparison_price_per_kg)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

                                    @elseif($material['type'] == 'sand')
                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                        <td style="text-align: center; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                @format($item->dimension_length)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                @format($item->dimension_width)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                @format($item->dimension_height)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
                                            @if($item->package_volume)
                                                @format($item->package_volume)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M3</td>
                                        <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
                                            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
                                        </td>
                                        <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
                                            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->package_price)
                                                @price($item->package_price)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->comparison_price_per_m3)
                                                @price($item->comparison_price_per_m3)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

                                    @elseif($material['type'] == 'ceramic')
                                        <td class="ceramic-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                        <td class="dim-cell ceramic-sticky-col col-dim-p border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                @format($item->dimension_length)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell ceramic-sticky-col col-dim-l border-left-none border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                @format($item->dimension_width)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell ceramic-sticky-col col-dim-t border-left-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_thickness))
                                                @format($item->dimension_thickness)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->surface ?? '-' }}</td>
                                        <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
                                        <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
                                        <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">{{ $item->packaging ?? '-' }}</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">
                                            @if($item->pieces_per_package)
                                                (  @format($item->pieces_per_package)
                                            @else
                                                <span>(  -</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">Lbr  )</td>
                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
                                            @if($item->coverage_per_package)
                                                @format($item->coverage_per_package)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M2</td>
                                        <td class="ceramic-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
                                            <div class="ceramic-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
                                        </td>
                                        <td class="ceramic-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
                                            <div class="ceramic-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
                                        </td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->price_per_package)
                                                @price($item->price_per_package)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packaging ?? '-' }}</td>
                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                            @if($item->comparison_price_per_m2)
                                                @price($item->comparison_price_per_m2)
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M2</td>
                                    @endif
                                    <td class="text-center action-cell">
                                        <div class="btn-group-compact">
                                            <a href="{{ route($material['type'] . 's.show', $item->id) }}" class="btn btn-primary-glossy btn-action open-modal" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route($material['type'] . 's.edit', $item->id) }}" class="btn btn-warning btn-action open-modal" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-danger btn-action"
                                                title="Hapus"
                                                onclick="deleteMaterial('{{ $material['type'] }}', {{ $item->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="material-footer-sticky">

                        <!-- Left Area: Pagination & Kanggo Logo -->
                        <div class="material-footer-left">
                            <!-- Kanggo A-Z Pagination (Logo & Letters) -->
                            @if(!request('search'))
                            <div class="kanggo-container" style="padding-top: 0;">
                                <div class="kanggo-logo">
                                    <img src="/Pagination/kangg.png" alt="Kanggo" style="height: 70px; width: auto;">
                                </div>
                                <div class="kanggo-letters" style="justify-content: center; margin-top: 3.5px; height: 80px;">
                                    @php
                                        $activeLetters = $material['active_letters'];
                                    @endphp

                                    @foreach(range('A', 'Z') as $index => $char)
                                        @php
                                            $isActive = in_array($char, $activeLetters);
                                            $imgIndex = $index + 1;
                                        @endphp

                                        @if($isActive)
                                            <a href="#{{ $material['type'] }}-letter-{{ $char }}" class="kanggo-img-link">
                                                <img src="/Pagination/{{ $imgIndex }}.png" alt="{{ $char }}" class="kanggo-img">
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Right Area: Hexagon Stats -->
                        <div class="material-footer-right">
                            <!-- HEXAGON PER MATERIAL -->
                            <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                                title="Total {{ $material['label'] }}">

                                <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                                    <img src="./assets/hex1.png"
                                        alt="Hexagon"
                                        style="width: 50px; height: 50px;">

                                    <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                        <span class="material-footer-count" style="font-size: 32px; line-height: 1;">
                                            @format($material['db_count'])
                                        </span>
                                    </div>
                                </div>

                                <span class="material-footer-label">
                                    {{ $material['label'] }}
                                </span>
                            </div>

                            <!-- HEXAGON TOTAL -->
                            <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                                title="Total Semua Material">

                                <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                                    <img src="./assets/hex2.png"
                                        alt="Hexagon"
                                        style="width: 50px; height: 50px;">

                                    <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                        <span class="material-footer-count" style="font-size: 32px; line-height: 1;">
                                            @format($grandTotal)
                                        </span>
                                    </div>
                                </div>

                                <span class="material-footer-label">
                                    Total Material
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="padding: 60px 40px; text-align: center; color: #64748b; background: #fff; border-radius: 12px; border: 1px dashed #e2e8f0; margin-top: 20px;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🔍</div>
                        <h4 style="margin: 0 0 8px 0; font-weight: 700; color: #0f172a;">Tidak ada data ditemukan</h4>
                        <p style="margin: 0 0 24px 0; font-size: 14px;">
                            @if(request('search'))
                                Pencarian untuk "<strong>{{ request('search') }}</strong>" di kategori {{ $material['label'] }} tidak membuahkan hasil.
                            @else
                                Belum ada data {{ strtolower($material['label']) }} yang tersedia.
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('materials.index', ['tab' => $material['type']]) }}" class="btn btn-secondary-glossy ">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Pencarian
                            </a>
                        @else
                            <a href="{{ route($material['type'] . 's.create') }}" class="btn btn-primary-glossy open-modal">
                                <i class="bi bi-plus-lg"></i> Tambah {{ $material['label'] }} Baru
                            </a>
                        @endif
                    </div>
                @endif
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
    const STORAGE_KEY = 'material_filter_preferences';
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
    const newMaterialData = @json(session('new_material'));
    let materialOrder = savedFilter.order || [];
    const navBlinkMaterial = localStorage.getItem('materialNavSearchBlink');
    const navSearchType = localStorage.getItem('materialNavSearchType');

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
        if (!Array.isArray(materialOrder)) {
            materialOrder = [];
        }
        materialOrder = materialOrder.filter(type => type !== newMaterialData.type);
        materialOrder.unshift(newMaterialData.type);
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
            } else {
                settingsMenu.classList.add('active');
                settingsToggle.classList.add('active');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!settingsToggle.contains(e.target) && !settingsMenu.contains(e.target)) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
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
                localStorage.setItem('materialActiveTab', materialType);
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
            btn.classList.remove('first-visible', 'last-visible');
        });

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
                    if (index === order.length - 1) {
                        tabButtons[type].classList.add('last-visible');
                    }
                }
            });
            
            // Always move settings dropdown to the end
            if (settingsDropdown) {
                tabContainer.appendChild(settingsDropdown);
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

        const searchCounts = hasSearchQuery ? getTabSearchCounts() : null;
        const visibleOrder = hasSearchQuery ? getSearchOrderedTabs(checkedMaterials, searchCounts) : materialOrder;

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
    const savedTab = window.__materialSavedTab || localStorage.getItem('materialActiveTab');
    updateTabVisibility(savedTab);
    document.documentElement.classList.remove('materials-booting');

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

        document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
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
    });

    async function closeModal() {
        if (isFormDirty) {
            const confirmed = await window.showConfirm({
                title: 'Batalkan Perubahan?',
                message: 'Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?',
                confirmText: 'Ya, Tutup',
                cancelText: 'Kembali',
                type: 'warning'
            });
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
                localStorage.setItem('materialActiveTab', currentTab);
            }
        });
    });

    function highlightMaterialRowElement(row) {
        if (!row) return;
        const container = row.closest('.table-container');
        if (!container) return;

        const existing = container.querySelector('.material-row-outline');
        if (existing) {
            existing.remove();
        }

        const containerRect = container.getBoundingClientRect();
        const rowRect = row.getBoundingClientRect();
        const outline = document.createElement('div');
        outline.className = 'material-row-outline';
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
        document.addEventListener('keydown', clear, { once: true });
        window.addEventListener('scroll', clear, { once: true, passive: true });
        const container = row.closest('.table-container');
        if (container) {
            container.addEventListener('scroll', clear, { once: true, passive: true });
        }
    }

    function focusNewMaterialRow() {
        if (newMaterialHandled) return;
        if (!newMaterialData || !newMaterialData.type || !newMaterialData.id) return;
        newMaterialHandled = true;

        const panel = document.querySelector(`.material-tab-panel[data-tab="${newMaterialData.type}"]`);
        if (panel) {
            setActiveTab(newMaterialData.type);
        }

        const row = panel ? panel.querySelector(`tbody tr[data-material-id="${newMaterialData.id}"]`) : null;
        if (!row) return;

        newMaterialRow = row;
        row.classList.add('material-row-new');
        scrollRowIntoContainer(row);
        window.setTimeout(() => {
            if (!newMaterialRow) return;
            newMaterialRow.__materialRowSweep = createMaterialRowSweep(newMaterialRow);
        }, 260);
        bindNewMaterialClear(row);
    }

    window.deleteMaterial = async function(type, id) {
        const endpointMap = {
            brick: 'bricks',
            cat: 'cats',
            cement: 'cements',
            sand: 'sands',
            ceramic: 'ceramics',
        };
        const labelMap = {
            brick: 'bata',
            cat: 'cat',
            cement: 'semen',
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
                localStorage.setItem('materialActiveTab', type);
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

    // Helper function to update active pagination letter
    function updateActivePaginationLetter(forcedHash = null) {
        const hash = forcedHash || window.location.hash || window.__materialHash || ''; // e.g., #brick-letter-A

        // Remove 'current' class from all pagination links
        document.querySelectorAll('.kanggo-img-link').forEach(link => {
            link.classList.remove('current');
        });

        // Default: mark first available letter in the active tab
        const activePanel = document.querySelector('.material-tab-panel.active') || document.querySelector('.material-tab-panel');
        if (!activePanel) return;

        // If hash exists and matches a link inside active tab, use it
        if (hash) {
            const matchingLink = activePanel.querySelector(`.kanggo-img-link[href="${hash}"]`);
            if (matchingLink) {
                matchingLink.classList.add('current');
                return;
            }
        }

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
        if (!cellText.toLowerCase().includes(queryLower)) return false;

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
            if (!text.toLowerCase().includes(queryLower)) return;

            const regex = new RegExp(escapedQuery, 'gi');
            let match = null;
            let lastIndex = 0;
            let nodeHasMatch = false;
            const fragment = document.createDocumentFragment();

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

    function scrollRowIntoContainer(row) {
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
            behavior: 'smooth'
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
            const clickTarget = th.querySelector('a') || th;
            const headerLabel = (th.textContent || '').replace(/\s+/g, ' ').trim();

            th.classList.add('material-search-jump');
            clickTarget.addEventListener('click', event => {
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                const matchRow = findMatchingRowForColumns(table, queryLower, colStart, colEnd);
                event.preventDefault();
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
    }

    // Add click handlers to pagination links to preserve current tab
    document.querySelectorAll('.kanggo-img-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor behavior

            // Save current active tab before navigation
            const activeTab = document.querySelector('.material-tab-btn.active');
            if (activeTab) {
                const currentTab = activeTab.dataset.tab;
                localStorage.setItem('materialActiveTab', currentTab);
            }

            const href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                const targetId = href.slice(1);

                // Update URL
                history.replaceState(null, null, href);
                window.__materialHash = href;

                // Update active pagination letter
                updateActivePaginationLetter(href);

                // Scroll to target in container with offset
                scrollToTargetInContainer(targetId);

                // Highlight after scroll
                window.setTimeout(() => highlightMaterialRow(targetId), 400);
            }
        });
    });

    // Handle page load with hash (prevent native jump)
    const initialHash = window.__materialHash || window.location.hash;
    if (initialHash) {
        // Force instant scroll to top (disable smooth behavior temporarily)
        document.documentElement.style.scrollBehavior = 'auto';
        window.scrollTo(0, 0);

        if (initialHash !== '#skip-page') {
            window.setTimeout(() => {
                const targetId = initialHash.slice(1);
                // Update active pagination letter
                updateActivePaginationLetter(initialHash);
                // Scroll to target
                scrollToTargetInContainer(targetId);
                // Highlight row
                window.setTimeout(() => highlightMaterialRow(targetId), 400);
                history.replaceState(null, null, initialHash);
            }, 500);
        }

        // Restore smooth scroll behavior
        window.setTimeout(() => {
            document.documentElement.style.scrollBehavior = '';
        }, 100);
    }

    // Handle hash change events
    window.addEventListener('hashchange', () => {
        const targetId = window.location.hash.slice(1);

        // Update active pagination letter
        updateActivePaginationLetter(window.location.hash);

        // Scroll to target
        scrollToTargetInContainer(targetId);

        // Highlight row
        window.setTimeout(() => highlightMaterialRow(targetId), 400);
    });

    // Initial update of active pagination letter on page load
    updateActivePaginationLetter();
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
