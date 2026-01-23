I've updated the store search functionality as requested.

Here are the changes made in `resources/views/stores/index.blade.php`:

1.  **Manual Search Only**:
    *   Added `data-search-manual="true"` to the search form.
    *   This opt-out flag tells the global `search-debounce.js` script to ignore this specific form, so it won't submit automatically while you type. You must now click the "Cari" button or press Enter.

2.  **Fixed Disappearing Search Icon**:
    *   Added `style="z-index: 10;"` to the search icon (`<i class="bi bi-search ...">`).
    *   This ensures the icon always stays visually on top of the input field, even when the input is focused or active.