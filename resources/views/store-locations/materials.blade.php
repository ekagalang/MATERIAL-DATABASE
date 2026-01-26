@extends('layouts.app')

@section('title', 'Material ' . $store->name)

@section('content')
<!-- Reuse inline scripts from materials.index -->
<script>
(function() {
    document.documentElement.classList.add('materials-booting');
    document.documentElement.classList.add('materials-lock');
})();
(function() {
    const savedTab = localStorage.getItem('materialActiveTab');
    if (savedTab) {
        window.__materialSavedTab = savedTab;
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
    function applyAllStickyOffsets() {
        const applyToSection = (sectionId, stickyClass) => {
            const panel = document.getElementById(sectionId);
            if (!panel) return;
            const tables = panel.querySelectorAll('table');
            tables.forEach(table => {
                const rows = table.querySelectorAll('tr');
                rows.forEach(row => {
                    const stickyCells = row.querySelectorAll('.' + stickyClass);
                    stickyCells.forEach(cell => {
                        cell.style.left = '';
                        cell.style.left = `${cell.offsetLeft}px`;
                    });
                });
            });
        };
        applyToSection('section-ceramic', 'ceramic-sticky-col');
        applyToSection('section-cat', 'cat-sticky-col');
        applyToSection('section-cement', 'cement-sticky-col');
    }

    window.addEventListener('load', applyAllStickyOffsets);
    window.addEventListener('resize', () => requestAnimationFrame(applyAllStickyOffsets));
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('.material-tab-btn')) {
            setTimeout(() => requestAnimationFrame(applyAllStickyOffsets), 50);
        }
    });
})();
</script>

<style>
/* Reuse styles from materials.index via copy-paste or shared file */
/* Minimal required overrides */
:root {
    --tab-foot-radius: 18px;
    --tab-active-bg: #91C6BC;
    overflow: hidden;
}
html { scroll-padding-top: 80px; scroll-behavior: smooth; }
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
    background: #91C6BC !important; /* Force green background */
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

  /* Override wrapper height for store view to accommodate the card */
  html.materials-lock .material-tab-wrapper,
  body.materials-lock .material-tab-wrapper {
      height: auto;
      flex: 1;
      min-height: 0; /* Important for nested scroll */
  }
</style>

<div class="d-flex flex-column h-100">
    <!-- Location Info Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white flex-shrink-0">
            <div class="card-body p-4" style="display: flex; justify-content: space-between;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="fw-bold mb-0">
                        {{ $store->name ?? 'Toko Tanpa Nama' }}
                    </h4>
                    <span class="text-secondary">•</span>
                    <p class="text-secondary mb-0 small d-flex align-items-center">
                        <i class="bi bi-geo-alt me-1"></i>
                        {{ $location->address ?? $location->city ?? 'Lokasi Tanpa Alamat' }}
                    </p>
                </div>
                
                <div class="d-flex align-items-center gap-3">   
                    <a href="{{ route('stores.show', $store) }}" class="btn-cancel"
                    style="border: 1px solid #891313; background-color: transparent; color: #891313;
                    padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Material Tabs (Same structure as index) -->
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
                            <form action="{{ url()->current() }}" method="GET" class="material-search-form manual-search" data-search-manual="true">
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
                                    <a href="{{ url()->current() }}?tab={{ $material['type'] }}" class="btn btn-secondary-glossy material-search-reset-btn">
                                        <i class="bi bi-x-lg"></i> Reset
                                    </a>
                                @endif
                            </form>
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

            <!-- Content Panels -->
            @if(count($materials) > 0)
                @foreach($materials as $material)
                    <div class="material-tab-panel {{ $material['type'] === $activeTab ? 'active' : 'hidden' }}" data-tab="{{ $material['type'] }}" id="section-{{ $material['type'] }}" style="margin-bottom: 24px;">
                        <div class="material-tab-card">
                        @if($material['data']->count() > 0)
                            <div class="table-container text-nowrap mb-3">
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
                                                    // Use current URL to stay on store page
                                                    return url()->current() . '?' . http_build_query($params);
                                                }
                                            }
                                            $brickSortable = [
                                                'type' => 'Jenis',
                                                'brand' => 'Merek',
                                                'form' => 'Bentuk',
                                                'dimension_length' => 'Dimensi (cm)',
                                                'package_volume' => 'Volume',
                                                'store' => 'Toko',
                                                'address' => 'Alamat',
                                                'price_per_piece' => 'Harga Beli',
                                                'comparison_price_per_m3' => 'Harga <br> Komparasi (/ M3)',
                                            ];
                                            $sandSortable = [
                                                'type' => 'Jenis',
                                                'brand' => 'Merek',
                                                'package_unit' => 'Kemasan',
                                                'dimension_length' => 'Dimensi Kemasan (M)',
                                                'package_volume' => 'Volume',
                                                'store' => 'Toko',
                                                'address' => 'Alamat',
                                                'package_price' => 'Harga Beli',
                                                'comparison_price_per_m3' => 'Harga <br> Komparasi (/ M3)',
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
                                                'comparison_price_per_kg' => 'Harga <br> Komparasi (/ Kg)',
                                            ];
                                            $cementSortable = [
                                                'type' => 'Jenis',
                                                'brand' => 'Merek',
                                                'sub_brand' => 'Sub Merek',
                                                'code' => 'Kode',
                                                'color' => 'Warna',
                                                'package_unit' => 'Kemasan',
                                                'dimension_length' => 'Dimensi (cm)',
                                                'package_weight_net' => 'Berat Bersih',
                                                'store' => 'Toko',
                                                'address' => 'Alamat',
                                                'package_price' => 'Harga Beli',
                                                'comparison_price_per_kg' => 'Harga <br> Komparasi (/ Kg)',
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
                                                'coverage_per_package' => 'Luas (M2 / Dus)',
                                                'dimension_length' => 'Dimensi (cm)',
                                                'store' => 'Toko',
                                                'address' => 'Alamat',
                                                'price_per_package' => 'Harga / Kemasan',
                                                'comparison_price_per_m2' => 'Harga Komparasi <br> (/ M2)',
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
                                                        <span>Dimensi (cm)</span>
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
                                                        <span>Dimensi (cm)</span>
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
                                                        <span>Berat Bersih</span>
                                                        @if(request('sort_by') == 'package_weight_net')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
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
                                                    <span>Dimensi (cm)</span>
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
                                                    <span>Nomor Seri<br>(   Kode Pembakaran)</span>
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
                                                    <span>Corak ({{ $ceramicSortable['color'] }})</span>
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
                                                    <span>Luas<br>(/ Dus)</span>
                                                    @if(request('sort_by') == 'coverage_per_package')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
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
                                            $groupValue = $item->brand ?? '';
                                            $groupValue = trim((string) $groupValue);
                                            return $groupValue !== '' ? strtoupper(substr($groupValue, 0, 1)) : '#';
                                        });
                                        $orderedGroups = collect();
                                        $isSorting = request()->filled('sort_by');
                                        if ($isSorting) {
                                            $orderedGroups['*'] = $material['data'];
                                        } else {
                                            $keys = $letterGroups->keys()->sort();
                                            foreach ($keys as $key) {
                                                $orderedGroups[$key] = $letterGroups[$key];
                                            }
                                        }
                                        $rowNumber = 1;
                                    @endphp
                                    <tbody>
                                        @foreach($orderedGroups as $letter => $items)
                                            @php
                                                $anchorId = $isSorting ? null : ($letter === '#' ? 'other' : $letter);
                                            @endphp
                                            @foreach($items as $item)
                                                @php
                                                    $rowAnchorId = (!$isSorting && $loop->first) ? $material['type'] . '-letter-' . $anchorId : null;
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
                                                    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" 
                                                    @endif
                                                        @if($material['type'] == 'ceramic') style="text-align: center;" 
                                                        @elseif($material['type'] == 'cement') style="text-align: center; width: 40px; min-width: 40px;"
                                                        @elseif($material['type'] == 'sand') style="text-align: center; width: 40px; min-width: 40px;"
                                                        @elseif($material['type'] == 'cat') style="text-align: center; width: 40px; min-width: 40px;"
                                                        @elseif($material['type'] == 'brick') style="text-align: center; width: 40px; min-width: 40px;"
                                                    @endif>
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
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px; font-size: 13px;">Pcs  )</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 12px;">
                                                            @if($item->coverage_per_package)
                                                                @format($item->coverage_per_package)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px; font-size: 12px;">M2</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->price_per_package)
                                                                @price($item->price_per_package)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 60px; min-width: 60px;">/ Dus</td>
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
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Add JS for tab switching + filter --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.overflow = '';

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
    const hasSearchQuery = searchQueryRaw.length > 0;
    const navBlinkMaterial = localStorage.getItem('materialNavSearchBlink');

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

        document.addEventListener('click', function(e) {
            if (!settingsToggle.contains(e.target) && !settingsMenu.contains(e.target)) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
            }
        });

        settingsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    const toggleCheckboxes = document.querySelectorAll('.material-setting-input');
    const allTabButtons = document.querySelectorAll('.material-tab-btn');
    const allTabPanels = document.querySelectorAll('.material-tab-panel');
    const allTabActions = document.querySelectorAll('.material-tab-action');
    const emptyState = document.getElementById('emptyMaterialState');

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

    const tabButtons = Array.from(allTabButtons);
    const tabPanels = Array.from(allTabPanels);

    function setActiveTab(materialType) {
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabPanels.forEach(panel => {
            panel.classList.remove('active');
            panel.classList.add('hidden');
        });
        allTabActions.forEach(action => action.classList.remove('active'));

        const btn = document.querySelector(`.material-tab-btn[data-tab="${materialType}"]`);
        const panel = document.getElementById(`section-${materialType}`);
        const tabAction = document.querySelector(`.material-tab-action[data-tab="${materialType}"]`);

        if (btn && panel) {
            btn.classList.remove('hidden');
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            panel.classList.remove('hidden');
            panel.classList.add('active');

            if (tabAction) {
                tabAction.classList.add('active');
            }

            const tableContainer = panel.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.scrollLeft = 0;
            }

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

    function saveFilterToLocalStorage(selected, order) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                selected: selected,
                order: order
            }));
        } catch (e) {
            // Ignore
        }
    }

    function reorderTabs(orderOverride = null) {
        const tabContainer = document.querySelector('.material-tabs');
        if (!tabContainer) return;

        const settingsDropdown = tabContainer.querySelector('.material-settings-dropdown');
        const order = Array.isArray(orderOverride) && orderOverride.length ? orderOverride : materialOrder;
        const tabMap = {};

        tabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            tabMap[tabType] = btn;
            btn.classList.remove('first-visible', 'last-visible');
        });

        if (order.length > 0) {
            order.forEach((type, index) => {
                if (tabMap[type]) {
                    tabContainer.appendChild(tabMap[type]);
                    if (index === 0) {
                        tabMap[type].classList.add('first-visible');
                    }
                    if (index === order.length - 1) {
                        tabMap[type].classList.add('last-visible');
                    }
                }
            });
        }

        if (settingsDropdown) {
            tabContainer.appendChild(settingsDropdown);
        }
    }

    function getTabSearchCounts() {
        const counts = {};
        tabButtons.forEach(btn => {
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

    function updateVisibleTabLegs() {
        tabButtons.forEach(btn => btn.classList.remove('first-visible', 'last-visible'));
        const visibleButtons = tabButtons.filter(btn => btn.style.display !== 'none');
        if (!visibleButtons.length) return;
        visibleButtons[0].classList.add('first-visible');
        visibleButtons[visibleButtons.length - 1].classList.add('last-visible');
    }

    function updateTabVisibility(preferredTab = null) {
        const checkedMaterials = [];
        const tabContainer = document.querySelector('.material-tabs');

        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            if (checkbox.checked) {
                checkedMaterials.push(materialType);
                if (!materialOrder.includes(materialType)) {
                    materialOrder.push(materialType);
                }
            }
        });

        materialOrder = materialOrder.filter(item => checkedMaterials.includes(item));

        const searchCounts = hasSearchQuery ? getTabSearchCounts() : null;
        const visibleOrder = hasSearchQuery ? getSearchOrderedTabs(checkedMaterials, searchCounts) : materialOrder;

        reorderTabs(visibleOrder);

        tabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            btn.style.display = checkedMaterials.includes(tabType) ? 'inline-flex' : 'none';
        });

        tabPanels.forEach(panel => {
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

        const visibleTabButtons = tabButtons.filter(btn => btn.style.display !== 'none');
        const hasVisibleTabs = visibleTabButtons.length > 0;

        if (emptyState) {
            emptyState.style.display = hasVisibleTabs ? 'none' : 'block';
        }

        if (tabContainer) {
            tabContainer.style.display = 'flex';
            tabContainer.classList.toggle('only-filter', !hasVisibleTabs);
        }
        const tabActionsContainer = document.querySelector('.material-tab-actions');
        if (tabActionsContainer) {
            tabActionsContainer.style.display = hasVisibleTabs ? 'flex' : 'none';
        }

        updateVisibleTabLegs();

        if (visibleOrder.length > 0 && hasVisibleTabs) {
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

        saveFilterToLocalStorage(checkedMaterials, materialOrder);
    }

    // Restore and initialize
    let materialOrder = savedFilter.order || [];

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

    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateCheckboxUI(this);
            updateTabVisibility();
        });
    });

    tabButtons.forEach(tab => {
        tab.addEventListener('click', () => {
            if (tab.style.display === 'none') return;
            setActiveTab(tab.getAttribute('data-tab'));
        });
    });

    if (savedFilter.selected && savedFilter.selected.length > 0) {
        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            checkbox.checked = savedFilter.selected.includes(materialType);
            updateCheckboxUI(checkbox);
        });
    } else {
        toggleCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            updateCheckboxUI(checkbox);
        });
        materialOrder = [];
    }

    const savedTab = window.__materialSavedTab || localStorage.getItem('materialActiveTab');
    updateTabVisibility(savedTab);
    document.documentElement.classList.remove('materials-booting');

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

    const resetFilterBtn = document.getElementById('resetMaterialFilter');
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function() {
            toggleCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                updateCheckboxUI(checkbox);
            });

            materialOrder = [];

            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // Ignore
            }

            updateTabVisibility();
        });
    }
});
</script>
@endsection

