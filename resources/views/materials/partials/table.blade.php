@php
    $showActions = $showActions ?? true;
    $showStoreInfo = $showStoreInfo ?? true;
@endphp
@if(isset($material['is_loaded']) && !$material['is_loaded'])
    <div class="material-tab-loading" data-url="{{ route('materials.tab', ['type' => $material['type']]) }}" style="position: relative; overflow: hidden; background: transparent; padding: 0;">
        {{-- Skeleton Loader CSS --}}
        <style>
            .material-skeleton-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            .material-skeleton-header {
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                height: 40px;
            }
            .material-skeleton-header th {
                border: 1px solid #cbd5e1 !important;
                background: #ffffff;
                vertical-align: top !important;
                box-sizing: border-box;
            }
            /* Single Header Height */
            .material-skeleton-header.single-header tr th {
                height: 40px !important;
                padding: 8px 12px !important;
                line-height: 1.1;
            }
            /* Double Header Group Row Height */
            .material-skeleton-header.has-dim-sub tr.dim-group-row th {
                height: 26px !important;
                padding: 6px 12px !important;
                line-height: 1.1;
            }
            /* Double Header Sub Row Height */
            .material-skeleton-header.has-dim-sub tr.dim-sub-row th {
                height: 14px !important;
                padding: 1px 2px !important;
                font-size: 11px !important;
            }
            
            .material-skeleton-row {
                height: 35px !important; /* Match real row height */
            }
            .material-skeleton-cell {
                border: 1px solid #f1f5f9;
                background-color: #ffffff;
                padding: 2px 8px !important;
                vertical-align: middle;
                height: 35px !important;
            }
            .skeleton-box {
                height: 16px;
                background: #f1f5f9;
                border-radius: 4px;
                width: 100%;
                position: relative;
                overflow: hidden;
            }
            .skeleton-box::after {
                content: "";
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                transform: translateX(-100%);
                background-image: linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0) 0,
                    rgba(255, 255, 255, 0.5) 20%,
                    rgba(255, 255, 255, 0.8) 60%,
                    rgba(255, 255, 255, 0)
                );
                animation: shimmer 2s infinite;
            }
            .skeleton-w-10 { width: 10%; }
            .skeleton-w-20 { width: 20%; }
            .skeleton-w-30 { width: 30%; }
            .skeleton-w-40 { width: 40%; }
            .skeleton-w-50 { width: 50%; }
            .skeleton-w-60 { width: 60%; }
            .skeleton-w-70 { width: 70%; }
            
            @keyframes shimmer {
                100% { transform: translateX(100%); }
            }
            
            .loader-overlay {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                padding: 24px 40px;
                border-radius: 16px;
                box-shadow: none;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
                border: none;
                z-index: 10;
            }
            
            .construction-spinner {
                width: 48px;
                height: 48px;
                position: relative;
                animation: spin 3s linear infinite;
            }
            .construction-spinner::before, .construction-spinner::after {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                border: 4px solid transparent;
                border-top-color: #f59e0b; /* Amber-500 */
                border-radius: 50%;
                animation: spin-reverse 1.5s linear infinite;
            }
            .construction-spinner::after {
                border-top-color: transparent;
                border-bottom-color: #0ea5e9; /* Sky-500 */
                animation: spin 2s linear infinite;
            }
            
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            @keyframes spin-reverse { 0% { transform: rotate(360deg); } 100% { transform: rotate(0deg); } }
        </style>

        {{-- Interactive Construction Spinner --}}
        <div class="loader-overlay">
            <div class="construction-spinner"></div>
            <div style="font-weight: 600; color: #334155; font-size: 15px; letter-spacing: 0.01em;">Memuat Data Material...</div>
        </div>

        {{-- Table Skeleton Background --}}
        <div class="table-container text-nowrap" style="opacity: 1; pointer-events: none;">
            <table class="material-skeleton-table">
                @switch($material['type'])
                    @case('brick')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-60"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('sand')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-60"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('cat')
                        <thead class="material-skeleton-header single-header">
                            <tr>
                                <th class="cat-sticky-col col-no" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="cat-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="text-align: start;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                @if($showStoreInfo)
                                <th style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell cat-sticky-col col-no" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell cat-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: start;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="text-align: left; width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="text-align: right; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 80px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('cement')
                    @case('nat')
                        <thead class="material-skeleton-header single-header">
                            <tr>
                                <th class="cement-sticky-col" rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="cement-sticky-col" rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="cement-sticky-col cement-sticky-edge" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th rowspan="2" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell cement-sticky-col" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell cement-sticky-col"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell cement-sticky-col cement-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('ceramic')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th class="ceramic-sticky-col col-no" rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="ceramic-sticky-col col-type" rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="ceramic-sticky-col col-dim-group" colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="ceramic-sticky-col col-brand ceramic-sticky-edge" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th class="ceramic-sticky-col col-dim-p" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th class="ceramic-sticky-col col-dim-l" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th class="ceramic-sticky-col col-dim-t" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell ceramic-sticky-col col-no" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-p" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-l" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-t" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @default
                        {{-- Fallback generic skeleton --}}
                        <thead class="material-skeleton-header">
                            <tr>
                                <th style="width: 50px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th style="width: 150px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="width: 120px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="width: 100px;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th><div class="skeleton-box skeleton-w-40"></div></th>
                                <th><div class="skeleton-box skeleton-w-60"></div></th>
                                <th style="width: 80px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-40"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                @endswitch
            </table>
        </div>
    </div>
@elseif($material['data']->count() > 0)
    <div class="table-container text-nowrap">
        <table>
            <thead class="{{ in_array($material['type'], ['brick','sand','ceramic','cement','cat']) ? 'has-dim-sub' : 'single-header' }}">
                @php
                  if (!function_exists('getMaterialSortUrl')) {
                        function getMaterialSortUrl($column, $currentSortBy, $currentDirection, $isStoreLocation = false, $store = null, $location = null) {
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

                            // Use appropriate route based on context
                            if ($isStoreLocation && $store && $location) {
                                return route('store-locations.materials', array_merge(['store' => $store->id, 'location' => $location->id], $params));
                            }

                            return route('materials.index', $params);
                        }
                    }

                    // Set context variables for sort URLs
                    $sortIsStoreLocation = isset($isStoreLocation) && $isStoreLocation;
                    $sortStore = $sortIsStoreLocation && isset($store) ? $store : null;
                    $sortLocation = $sortIsStoreLocation && isset($location) ? $location : null;

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
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Volume</span>
                                        @if(request('sort_by') == 'package_volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('price_per_piece', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
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
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Volume</span>
                                        @if(request('sort_by') == 'package_volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
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
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('color_code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('color_name', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Berat<br>Bersih</span>
                                        @if(request('sort_by') == 'package_weight_net')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('purchase_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                <th class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            
                        @elseif(in_array($material['type'], ['cement', 'nat']))
                            <tr class="dim-group-row">
                                <th class="cement-sticky-col" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable cement-sticky-col" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Berat<br>Bersih</span>
                                        @if(request('sort_by') == 'package_weight_net')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 15s0px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>

                        @elseif($material['type'] == 'ceramic')
                        <tr class="dim-group-row">
                            <th class="ceramic-sticky-col col-no" rowspan="2" style="text-align: center;">No</th>
                            <th class="sortable ceramic-sticky-col col-type" rowspan="2" style="text-align: left;">
                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('surface', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('packaging', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                <a href="{{ getMaterialSortUrl('coverage_per_package', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Luas<br>( / Dus )</span>
                                    @if(request('sort_by') == 'coverage_per_package')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                                                            @if($showStoreInfo)
                                                            <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                    <span>{{ $ceramicSortable['address'] }}</span>
                                                                    @if(request('sort_by') == 'address')
                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                    @else
                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                    @endif
                                                                </a>
                                                            </th>
                                                            @endif                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('price_per_package', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
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
                                                                <a href="{{ getMaterialSortUrl('comparison_price_per_m2', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                    <span>Harga Komparasi</span>
                                                                    @if(request('sort_by') == 'comparison_price_per_m2')
                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                                    @else
                                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                                    @endif
                                                                </a>
                                                            </th>
                                                            @if($showActions)
                                                            <th rowspan="2" class="action-cell">Aksi</th>
                                                            @endif
                                                        </tr>
                                                        <tr class="dim-sub-row">
                                                            <th class="ceramic-sticky-col col-dim-p" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                                            <th class="ceramic-sticky-col col-dim-l" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                                            <th class="ceramic-sticky-col col-dim-t" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                                        </tr>
                                                    @endif                </thead>
                                                                                        @php
                                                                                            $letterGroups = $material['data']->groupBy(function ($item) use ($material) {
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
                                                                                                                                                            // Modified: Sort by Type (Jenis) alphabetically as default, instead of grouping by Brand
                                                                                                                                                            $orderedGroups['*'] = $material['data']->sortBy('type');
                                                                                                                                                        }                                                                                            $rowNumber = 1;
                                                                                            $seenAnchors = [];
                                                                                        @endphp
                                                                                        <tbody>
                                                                                            @foreach($orderedGroups as $letter => $items)
                                                                                                @foreach($items as $item)
                                                                                                    @php
                                                                                                        $brandFirst = $item->brand ?? '';
                                                                                                        $brandFirst = trim((string) $brandFirst);
                                                                                                        $rowLetter = $brandFirst !== '' ? strtoupper(substr($brandFirst, 0, 1)) : '#';
                                                                                                        if (!ctype_alpha($rowLetter)) {
                                                                                                            $rowLetter = '#';
                                                                                                        }
                                                    
                                                                                                        $rowAnchorId = null;
                                                                                                        if (!$defaultSort && !isset($seenAnchors[$rowLetter])) {
                                                                                                            $anchorSuffix = $rowLetter === '#' ? 'other' : $rowLetter;
                                                                                                            $rowAnchorId = $material['type'] . '-letter-' . $anchorSuffix;
                                                                                                            $seenAnchors[$rowLetter] = true;
                                                                                                        }                                $searchParts = array_filter([
                                    $item->type ?? null,
                                    $item->material_name ?? null,
                                    $item->cat_name ?? null,
                                    $item->cement_name ?? null,
                                    $item->nat_name ?? null,
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
                                elseif(in_array($material['type'], ['cement', 'nat'])) $stickyClass = 'cement-sticky-col';
                            @endphp
                    <tr data-material-tab="{{ $material['type'] }}" data-material-id="{{ $item->id }}" data-material-kind="{{ $item->type ?? $item->nat_name ?? '' }}" data-material-search="{{ $searchValue }}">
                        {{--  ... ROW CONTENT ... --}}
                        {{--  I will include the row content here but simplified for brevity as it is huge and repetitive in the original file. 
                              Wait, I need to copy the FULL content to be correct. 
                              I will copy the exact row logic from the original file.
                        --}}
                        @include('materials.partials.row-content', ['material' => $material, 'item' => $item, 'rowNumber' => $rowNumber, 'stickyClass' => $stickyClass, 'rowAnchorId' => $rowAnchorId])
                        {{--  Wait, extracting row content to another partial might be better given the size. 
                              But I can put it inline. 
                              For now, I will assume the row content is pasted here. 
                              I'll use the 'replace' tool on index.blade.php so I don't need to rewrite the whole file manually.
                              But creating the partial requires the content. 
                        --}}
                        
                        @if($showActions)
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
                        @endif
                    </tr>
                    @php $rowNumber++; @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="material-footer-sticky">

            @if(!(isset($isStoreLocation) && $isStoreLocation))
            <!-- Left Area: Pagination & Kanggo Logo (Only for materials/index) -->
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
                            if ($activeLetters instanceof \Illuminate\Support\Collection) {
                                $activeLetters = $activeLetters->toArray();
                            }
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
            @endif

            <!-- Hexagon Stats or Navigation -->
            @if(isset($isStoreLocation) && $isStoreLocation && isset($allMaterials))
                <!-- HEXAGON NAVIGATION FOR STORE LOCATION (Left Aligned) -->
                <div class="material-footer-right" style="width: 100%; justify-content: flex-start; margin-top: 8px;">
                    <!-- Total Hexagon (First - leftmost) -->
                    <div class="material-nav-hex-block material-footer-hex-block" data-tab="total" style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: all 0.2s ease;"
                        title="Total Semua Material">
                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex2.png') }}" alt="Total" style="width: 50px; height: 50px;">
                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($grandTotal)
                                </span>
                            </div>
                        </div>
                        <span class="material-footer-label">Total</span>
                    </div>

                    <!-- Material Type Hexagons -->
                    @foreach($allMaterials as $mat)
                    <div class="material-nav-hex-block material-footer-hex-block" data-tab="{{ $mat['type'] }}" style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: all 0.2s ease;"
                        title="Material {{ $mat['label'] }}">
                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex3.png') }}" alt="{{ $mat['label'] }}" style="width: 50px; height: 50px;">
                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($mat['count'])
                                </span>
                            </div>
                        </div>
                        <span class="material-footer-label">{{ $mat['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <!-- Right Area: Hexagon Stats (for materials/index) -->
                <div class="material-footer-right">
                    <!-- HEXAGON PER MATERIAL -->
                    <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                        title="Total {{ $material['label'] }}">

                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="./assets/hex1.png"
                                alt="Hexagon"
                                style="width: 50px; height: 50px;">

                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
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
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($grandTotal)
                                </span>
                            </div>
                        </div>

                        <span class="material-footer-label">
                            Total Material
                        </span>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Tidak Ada Material yang Ditampilkan</p>
            <p style="font-size: 14px; color: #94a3b8;">Pilih material yang ingin ditampilkan dari dropdown <strong>"Filter"</strong> di atas.</p>
        </div>
    @endif
