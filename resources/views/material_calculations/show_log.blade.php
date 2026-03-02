@extends('layouts.app')

@section('content') 
<div class="show-log-scope">
    <!-- Header -->
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 style="color: var(--special-text-color); font-weight: var(--special-font-weight); -webkit-text-stroke: var(--special-text-stroke); font-size: 32px;" class="mb-1">
                    Detail Perhitungan
                </h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('material-calculations.log') }}" class="btn-cancel" style="border: 1px solid #64748b; background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('material-calculations.edit', $materialCalculation) }}" class="btn-action" style="background-color: #f59e0b; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <button type="button" class="btn-action" onclick="window.print()" style="background-color: #0ea5e9; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>

    @php
        $costPerM2 = $materialCalculation->wall_area > 0 ? $materialCalculation->total_material_cost / $materialCalculation->wall_area : 0;

        // Retrieve dynamic Formula Name
        $params = $materialCalculation->calculation_params ?? [];
        $isBundleCalculation = (bool) ($params['is_bundle'] ?? false);
        $bundleName = trim((string) ($params['bundle_name'] ?? ''));
        if ($bundleName === '') {
            $bundleName = 'Paket Pekerjaan';
        }

        $decodeArrayPayload = static function (mixed $value): array {
            if (is_array($value)) {
                return $value;
            }
            if (!is_string($value) || trim($value) === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                $decoded = json_decode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            }

            return is_array($decoded) ? $decoded : [];
        };

        $rawBundleItems = $decodeArrayPayload($params['bundle_items'] ?? []);
        if (empty($rawBundleItems)) {
            $rawBundleItems = $decodeArrayPayload($params['work_items_payload'] ?? []);
        }

        $bundleSelectedResult = $decodeArrayPayload($params['bundle_selected_result'] ?? []);
        $resultBundleItems = is_array($bundleSelectedResult['bundle_items'] ?? null)
            ? array_values(array_filter($bundleSelectedResult['bundle_items'], static fn($item) => is_array($item)))
            : [];
        if (empty($resultBundleItems) && !empty($rawBundleItems)) {
            $resultBundleItems = is_array($rawBundleItems)
                ? array_values(array_filter($rawBundleItems, static fn($item) => is_array($item)))
                : [];
        }

        $resolveTaxonomyValue = static function (mixed $value): string {
            if (is_array($value)) {
                foreach ($value as $entry) {
                    $text = trim((string) $entry);
                    if ($text !== '') {
                        return $text;
                    }
                }

                return '';
            }

            return trim((string) $value);
        };
        $bundleDisplayItems = [];
        foreach ($rawBundleItems as $bundleIndex => $bundleItemRaw) {
            if (!is_array($bundleItemRaw)) {
                continue;
            }

            $resultItem = is_array($resultBundleItems[$bundleIndex] ?? null) ? $resultBundleItems[$bundleIndex] : [];
            $workTypeCode = trim((string) ($bundleItemRaw['work_type'] ?? ($resultItem['work_type'] ?? '')));
            if ($workTypeCode === '') {
                continue;
            }

            $formulaMeta = \App\Services\FormulaRegistry::find($workTypeCode);
            $workTypeName = trim(
                (string) ($bundleItemRaw['work_type_name'] ?? ($resultItem['work_type_name'] ?? ($formulaMeta['name'] ?? ''))),
            );
            if ($workTypeName === '') {
                $workTypeName = ucwords(str_replace('_', ' ', $workTypeCode));
            }

            $title = trim((string) ($bundleItemRaw['title'] ?? ($resultItem['title'] ?? '')));
            if ($title === '') {
                $title = 'Item Pekerjaan ' . ($bundleIndex + 1);
            }

            $rowKind = strtolower(trim((string) ($bundleItemRaw['row_kind'] ?? ($resultItem['row_kind'] ?? 'item'))));
            if (!in_array($rowKind, ['area', 'field', 'item'], true)) {
                $rowKind = 'item';
            }

            $lengthItem = \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['wall_length'] ?? null);
            $heightItem = \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['wall_height'] ?? null);
            $areaItem = \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['area'] ?? null);
            $isRollagItem = $workTypeCode === 'brick_rollag';
            $isPlinthCeramicItem = $workTypeCode === 'plinth_ceramic';
            if (
                ($areaItem === null || $areaItem <= 0) &&
                !$isRollagItem &&
                $lengthItem !== null &&
                $heightItem !== null &&
                $lengthItem > 0 &&
                $heightItem > 0
            ) {
                $areaItem = $isPlinthCeramicItem ? $lengthItem * ($heightItem / 100) : $lengthItem * $heightItem;
            }
            if ($areaItem !== null && $areaItem <= 0) {
                $areaItem = null;
            }

            $grandTotalItem = (float) ($bundleItemRaw['grand_total'] ?? ($resultItem['grand_total'] ?? 0));
            $workFloorItem = trim((string) ($bundleItemRaw['work_floor'] ?? ''));
            if ($workFloorItem === '') {
                $workFloorItem = $resolveTaxonomyValue($bundleItemRaw['work_floors'] ?? '');
            }
            if ($workFloorItem === '') {
                $workFloorItem = trim((string) ($resultItem['work_floor'] ?? ''));
            }

            $workAreaItem = trim((string) ($bundleItemRaw['work_area'] ?? ''));
            if ($workAreaItem === '') {
                $workAreaItem = $resolveTaxonomyValue($bundleItemRaw['work_areas'] ?? '');
            }
            if ($workAreaItem === '') {
                $workAreaItem = trim((string) ($resultItem['work_area'] ?? ''));
            }

            $workFieldItem = trim((string) ($bundleItemRaw['work_field'] ?? ''));
            if ($workFieldItem === '') {
                $workFieldItem = $resolveTaxonomyValue($bundleItemRaw['work_fields'] ?? '');
            }
            if ($workFieldItem === '') {
                $workFieldItem = trim((string) ($resultItem['work_field'] ?? ''));
            }

            $heightItemLabel = in_array(
                $workTypeCode,
                ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
                true,
            )
                ? 'LEBAR'
                : 'TINGGI';

            $bundleDisplayItems[] = [
                'title' => $title,
                'work_type' => $workTypeCode,
                'work_type_name' => $workTypeName,
                'row_kind' => $rowKind,
                'work_floor' => $workFloorItem,
                'work_area' => $workAreaItem,
                'work_field' => $workFieldItem,
                'length' => $lengthItem,
                'height' => $heightItem,
                'height_label' => $heightItemLabel,
                'height_unit' => $isPlinthCeramicItem ? 'cm' : 'm',
                'area' => $areaItem,
                'mortar_thickness' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['mortar_thickness'] ?? null),
                'layer_count' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['layer_count'] ?? null),
                'grout_thickness' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['grout_thickness'] ?? null),
                'ceramic_length' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['ceramic_length'] ?? null),
                'ceramic_width' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['ceramic_width'] ?? null),
                'ceramic_thickness' => \App\Helpers\NumberHelper::parseNullable($bundleItemRaw['ceramic_thickness'] ?? null),
                'is_rollag' => $isRollagItem,
                'grand_total' => $grandTotalItem,
            ];
        }
        $bundleItemCount = count($bundleDisplayItems);

        $workType = $params['work_type'] ?? ($bundleDisplayItems[0]['work_type'] ?? 'brick_half');
        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $isBundleCalculation
            ? $bundleName
            : ($formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding');

        $brickType = $materialCalculation->brick ? $materialCalculation->brick->type : 'Merah';
        $isPainting = in_array($workType, ['painting', 'wall_painting'], true);
        $isGroutOnly = $workType === 'grout_tile';
        $isTileInstall = $workType === 'tile_installation';
        $isFloorType = in_array($workType, ['floor_screed', 'coating_floor'], true);
        $isRollag = $workType === 'brick_rollag';

        $lengthValue = $params['wall_length'] ?? $materialCalculation->wall_length ?? null;
        $heightValue = $isRollag ? null : ($params['wall_height'] ?? $materialCalculation->wall_height ?? null);
        $areaValue = $params['area'] ?? $materialCalculation->wall_area ?? null;
        if (!$isRollag && !$areaValue && $lengthValue !== null && $heightValue !== null) {
            $areaValue = $lengthValue * $heightValue;
        }

        $groutThickness = $params['grout_thickness'] ?? null;
        $mortarThickness = $params['mortar_thickness'] ?? $materialCalculation->mortar_thickness ?? 2.0;
        $layerCount = $params['layer_count'] ?? $params['paint_layers'] ?? $params['painting_layers'] ?? null;

        $ceramicLength = $params['ceramic_dimensions']['length'] ?? $params['ceramic_length'] ?? optional($materialCalculation->ceramic)->dimension_length;
        $ceramicWidth = $params['ceramic_dimensions']['width'] ?? $params['ceramic_width'] ?? optional($materialCalculation->ceramic)->dimension_width;
        $ceramicThickness = $params['ceramic_dimensions']['thickness'] ?? $params['ceramic_thickness'] ?? optional($materialCalculation->ceramic)->dimension_thickness;
        $ceramicLengthValue = (is_numeric($ceramicLength) && $ceramicLength > 0) ? $ceramicLength + 0 : null;
        $ceramicWidthValue = (is_numeric($ceramicWidth) && $ceramicWidth > 0) ? $ceramicWidth + 0 : null;
        $ceramicThicknessValue = (is_numeric($ceramicThickness) && $ceramicThickness > 0) ? $ceramicThickness + 0 : null;
        $hasCeramicDimensions = $ceramicLengthValue && $ceramicWidthValue;
        $showCeramicRow = ($materialCalculation->ceramic_quantity ?? 0) > 0 || $isGroutOnly;
        $heightLabel = in_array(
            $workType,
            ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
            true,
        )
            ? 'LEBAR'
            : 'TINGGI';

        // Deteksi kebutuhan material untuk tampilan dinamis
        $hasBrick = $materialCalculation->brick_quantity > 0;
        $hasCement = $materialCalculation->cement_quantity_sak > 0;
        $hasSand = $materialCalculation->sand_m3 > 0;
        $hasCat = $materialCalculation->cat_quantity > 0;
        $hasCeramic = ($materialCalculation->ceramic_quantity ?? 0) > 0;
        $hasNat = ($materialCalculation->nat_quantity ?? 0) > 0;

        $projectAddress = trim((string) ($materialCalculation->project_address ?? ''));
        $projectPlaceId = trim((string) ($materialCalculation->project_place_id ?? ''));
        $projectLatitude = $materialCalculation->project_latitude;
        $projectLongitude = $materialCalculation->project_longitude;
        $hasProjectCoordinates = is_numeric($projectLatitude) && is_numeric($projectLongitude);
        $hasProjectLocation = $projectAddress !== '' || $hasProjectCoordinates || $projectPlaceId !== '';
        $projectMapQuery = '';
        if ($hasProjectCoordinates) {
            $projectMapQuery = $projectLatitude . ',' . $projectLongitude;
        } elseif ($projectAddress !== '') {
            $projectMapQuery = $projectAddress;
        } elseif ($projectPlaceId !== '') {
            $projectMapQuery = 'place_id:' . $projectPlaceId;
        }
        $projectMapEmbedUrl = $projectMapQuery !== ''
            ? 'https://maps.google.com/maps?q=' . urlencode($projectMapQuery) . '&z=15&output=embed'
            : null;
        
        // Calculate rowSpan based on active materials + Water (always 1)
        $rowSpan = 1 + ($hasBrick ? 1 : 0) + ($hasCement ? 1 : 0) + ($hasSand ? 1 : 0) + ($hasCat ? 1 : 0) + ($showCeramicRow ? 1 : 0) + ($hasNat ? 1 : 0);
        
        // Track rendered rows to place rowspan on the first one
        $isFirstRow = true;
    @endphp

    @if ($hasProjectLocation)
        <div class="container mb-3">
            <div class="card p-3 shadow-sm border-0"
                style="background-color: #fdfdfd; border-radius: 12px;">
                @if ($projectMapEmbedUrl)
                    <iframe
                        src="{{ $projectMapEmbedUrl }}"
                        width="100%"
                        height="240"
                        style="border: 0; border-radius: 10px;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                    @if ($projectAddress !== '')
                        <div class="mt-2 small text-muted">{{ $projectAddress }}</div>
                    @endif
                @else
                    <div class="text-muted">Lokasi proyek tidak tersedia.</div>
                @endif
            </div>
        </div>
    @endif

    {{-- Header Info: Item Pekerjaan Details Card --}}
    @if ($isBundleCalculation && $bundleItemCount > 0)
        @include('material_calculations.partials.show_log_bundle_dropdown', [
            'bundleDisplayItems' => $bundleDisplayItems,
            'rawBundleItems' => $rawBundleItems,
            'formulaName' => $formulaName,
            'materialCalculation' => $materialCalculation,
        ])
    @else
    <div class="container mb-3">
        <div class="card p-3 shadow-sm border-0 preview-params-sticky"
            style="background-color: #fdfdfd; border-radius: 12px;">
            <div class="d-flex flex-wrap align-items-end gap-3 justify-content-start preview-param-row">
                {{-- ===== GRUP UTAMA: Item Pekerjaan + Dimensi ===== --}}

                {{-- Jenis Item Pekerjaan --}}
                <div style="flex: 1; min-width: 250px;">
                    <label class="fw-bold mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <i class="bi bi-briefcase me-1"></i>Jenis Item Pekerjaan
                    </label>
                    <div class="form-control fw-bold border-secondary text-dark"
                        style="background-color: #e9ecef; opacity: 1;">
                        {{ $formulaName }}
                    </div>
                </div>

                {{-- Panjang --}}
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                        style="font-size: 0.75rem;">
                        <span class="badge bg-light border">PANJANG</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">
                            @format($lengthValue)</div>
                        <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                    </div>
                </div>

                @if (!$isRollag)
                    {{-- Tinggi/Lebar --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge bg-light border">{{ $heightLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">
                                @format($heightValue)</div>
                            <span class="input-group-text bg-light small px-1"
                                style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>

                    {{-- Luas --}}
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center bg-white text-danger px-1"
                                style="border-color: #dc3545;">@format($areaValue ?? 0)</div>
                            <span class="input-group-text bg-danger text-white small px-1"
                                style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                        </div>
                    </div>
                @endif

                {{-- ===== SEPARATOR / GAP ===== --}}
                <div style="flex: 0 0 auto; width: 10px;"></div>

                {{-- ===== GRUP TAMBAHAN: Parameter Lainnya ===== --}}

                {{-- Tebal Spesi (tidak untuk Pasang Nat atau Pengecatan) --}}
                @if (!in_array($workType, ['grout_tile', 'painting', 'wall_painting'], true))
                    <div style="flex: 0 0 auto; width: 100px;">
                        @php
                            $paramLabel = 'TEBAL ADUKAN';
                            $paramUnit = 'cm';
                            $paramValue = $mortarThickness ?? 2.0;
                            $badgeClass = 'bg-light';
                        @endphp
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge {{ $badgeClass }} border">{{ $paramLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">
                                {{ $paramValue }}</div>
                            <span class="input-group-text bg-light small px-1"
                                style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                        </div>
                    </div>
                @endif

                {{-- Tingkat (hanya untuk Rollag) --}}
                @if ($workType === 'brick_rollag')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge bg-warning border">TINGKAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #fffbeb; border-color: #fcd34d;">
                                {{ $params['layer_count'] ?? 1 }}</div>
                            <span class="input-group-text bg-warning small px-1"
                                style="font-size: 0.7rem;">Lapis</span>
                        </div>
                    </div>
                @endif

                {{-- Lapis Pengecatan --}}
                @if ($isPainting)
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge bg-primary text-white border border-primary">LAPIS CAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #dbeafe; border-color: #3b82f6;">
                                {{ $layerCount ?? 1 }}
                            </div>
                            <span class="input-group-text bg-primary text-white small px-1"
                                style="font-size: 0.7rem;">Lapisan</span>
                        </div>
                    </div>
                @endif

                {{-- Tebal Nat (untuk Pasang Keramik dan Pasang Nat) --}}
                @if ($workType === 'tile_installation' || $workType === 'grout_tile')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge bg-info text-white border">TEBAL NAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #e0f2fe; border-color: #38bdf8;">
                                {{ $groutThickness ?? 3 }}</div>
                            <span class="input-group-text bg-info text-white small px-1"
                                style="font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                @endif

                {{-- Panjang Keramik (untuk Pasang Nat saja) --}}
                @if ($isGroutOnly && $ceramicLengthValue)
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">P.
                                KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #fef3c7; border-color: #fde047;">
                                {{ $ceramicLengthValue }}</div>
                            <span class="input-group-text text-white small px-1"
                                style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                @endif

                {{-- Lebar Keramik (untuk Pasang Nat saja) --}}
                @if ($isGroutOnly && $ceramicWidthValue)
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">L.
                                KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #fef3c7; border-color: #fde047;">
                                {{ $ceramicWidthValue }}</div>
                            <span class="input-group-text text-white small px-1"
                                style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                @endif

                {{-- Tebal Keramik (untuk Pasang Nat saja) --}}
                @if ($isGroutOnly && $ceramicThicknessValue)
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                            style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">T.
                                KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1"
                                style="background-color: #fef3c7; border-color: #fde047;">
                                {{ $ceramicThicknessValue }}</div>
                            <span class="input-group-text text-white small px-1"
                                style="background-color: #f59e0b; font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="container">
    <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
        <div class="table-responsive detail-table-wrap">
            <style>
                /* Global Text Styling for All Elements (scoped) */
                .show-log-scope h1,
                .show-log-scope h2,
                .show-log-scope h3,
                .show-log-scope h4,
                .show-log-scope h5,
                .show-log-scope h6,
                .show-log-scope p,
                .show-log-scope span,
                .show-log-scope div,
                .show-log-scope a,
                .show-log-scope label,
                .show-log-scope input,
                .show-log-scope select,
                .show-log-scope textarea,
                .show-log-scope button,
                .show-log-scope th,
                .show-log-scope td,
                .show-log-scope i,
                .show-log-scope strong {
                    font-family: 'Nunito', sans-serif !important;
                    color: #000000 !important;
                    font-weight: 700 !important;
                }

                /* Text styling override for table body */
                #show_log td,
                #show_log td * {
                    color: #000000 !important;
                    -webkit-text-stroke: 0 !important;
                    text-shadow: none !important;
                }

                /* Override for input/form controls */
                .show-log-scope .form-control,
                .show-log-scope .input-group-text {
                    color: #1e293b !important;
                    -webkit-text-stroke: 0 !important;
                    text-shadow: none !important;
                }

                .show-log-scope .preview-param-row {
                    justify-content: flex-start;
                    min-width: 0;
                }

                .show-log-scope .preview-param-row.preview-param-row-with-dropdown {
                    overflow: visible;
                }

                .show-log-scope .preview-param-items-dropdown {
                    position: relative;
                    z-index: 95;
                }

                .show-log-scope .preview-param-items-dropdown .dropdown-menu {
                    z-index: 1305;
                    overflow-x: hidden;
                    --bundle-col-work: minmax(240px, 1fr);
                    --bundle-col-size: minmax(260px, 1fr);
                    --bundle-col-support: minmax(260px, 1fr);
                }

                .show-log-scope .preview-param-items-dropdown .bundle-param-dropdown-menu {
                    width: 100%;
                    min-width: 100%;
                    max-width: min(100%, calc(100vw - 24px));
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline {
                    z-index: auto;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .dropdown-menu {
                    position: static !important;
                    inset: auto !important;
                    transform: none !important;
                    float: none !important;
                    margin-top: 0.5rem !important;
                    width: 100% !important;
                    min-width: 100% !important;
                    max-width: 100% !important;
                    max-height: none !important;
                    overflow-y: visible !important;
                    overflow-x: hidden;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-dropdown-menu {
                    padding: 0 !important;
                    box-shadow: none !important;
                    border: 0 !important;
                    background: transparent !important;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-card {
                    margin-bottom: 0.35rem;
                    padding: 0.22rem 0.35rem !important;
                    border-radius: 7px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-card:last-child {
                    margin-bottom: 0;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-layout {
                    gap: 0.4rem 0.55rem;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section label {
                    font-size: 0.6rem !important;
                    margin-bottom: 0.2rem !important;
                    letter-spacing: 0.25px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section-fields {
                    gap: 0.25rem 0.3rem;
                    min-height: 28px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--sm {
                    width: 74px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--md {
                    width: 88px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--ceramic {
                    width: 84px;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section-fields .badge {
                    font-size: 0.5rem !important;
                    padding: 0.08rem 0.24rem !important;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-worktype-value {
                    margin-top: 15px;
                    font-size: 0.7rem;
                    min-height: 26px;
                    padding: 0.16rem 0.34rem;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .form-control {
                    font-size: 0.7rem;
                    min-height: 26px;
                    padding: 0.12rem 0.28rem !important;
                    line-height: 1.2;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .input-group-text {
                    font-size: 0.56rem !important;
                    padding: 0.1rem 0.24rem !important;
                    min-height: 26px;
                    line-height: 1.1;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .dropdown-toggle {
                    font-size: 0.72rem;
                    padding: 0.3rem 0.56rem;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle {
                    color: #4b5563 !important;
                    text-decoration: none !important;
                    border: 0 !important;
                    border-bottom: 0 !important;
                    background: transparent !important;
                    box-shadow: none !important;
                    font-size: 0.68rem;
                    letter-spacing: 0.35px;
                    padding: 0 !important;
                    display: inline-flex;
                    align-items: center;
                    cursor: pointer;
                    appearance: none;
                    -webkit-appearance: none;
                    line-height: 1.1;
                    pointer-events: auto !important;
                    outline: none !important;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle::after {
                    margin-left: 0.35rem;
                    vertical-align: middle;
                    border-top-color: #6b7280;
                }

                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle:hover,
                .show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle:focus {
                    color: #111827 !important;
                    text-decoration: none;
                }

                .show-log-scope .bundle-param-item-card {
                    background: #ffffff;
                    border-radius: 10px;
                    border: 1px solid #e5e7eb;
                }

                .show-log-scope .bundle-param-item-layout {
                    display: grid;
                    grid-template-columns: var(--bundle-col-work) var(--bundle-col-size) var(--bundle-col-support);
                    gap: 0.85rem 1rem;
                    align-items: start;
                    width: 100%;
                    min-width: 0;
                }

                .show-log-scope .bundle-param-section {
                    min-width: 0;
                    overflow: hidden;
                }

                .show-log-scope .bundle-param-section-fields {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 0.5rem 0.6rem;
                    align-items: flex-end;
                    justify-content: flex-start;
                    width: 100%;
                    min-height: 46px;
                    overflow: hidden;
                }

                .show-log-scope .bundle-param-field {
                    flex: 0 0 auto;
                }

                .show-log-scope .bundle-param-field--sm {
                    width: 100px;
                }

                .show-log-scope .bundle-param-field--md {
                    width: 120px;
                }

                .show-log-scope .bundle-param-field--ceramic {
                    width: 110px;
                }

                .show-log-scope .bundle-param-section-fields .badge {
                    font-size: 0.62rem;
                    padding: 0.2rem 0.45rem;
                    letter-spacing: 0.2px;
                }

                .show-log-scope .bundle-param-empty {
                    min-width: 96px;
                    min-height: 36px;
                    padding: 0 0.75rem;
                    border: 1px dashed #d1d5db;
                    border-radius: 0.4rem;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #f8fafc;
                    color: #9ca3af !important;
                    font-weight: 600;
                    font-size: 0.85rem;
                }

                .show-log-scope .bundle-param-worktype-value {
                    width: 100%;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                @media (max-width: 1199.98px) {
                    .show-log-scope .bundle-param-item-layout {
                        min-width: 100%;
                    }
                }

                @media (max-width: 991.98px) {
                    .show-log-scope .bundle-param-item-layout {
                        grid-template-columns: 1fr;
                    }
                }

                .show-log-scope .preview-params-sticky.preview-params-sticky--bundle {
                    padding: 0.5rem 0.85rem !important;
                }

                .show-log-scope .preview-params-sticky.preview-params-sticky--bundle .preview-param-row {
                    gap: 0.45rem !important;
                }

                .show-log-scope .preview-params-sticky.preview-params-sticky--bundle .preview-param-items-dropdown-inline > .mb-2 {
                    margin-bottom: 0 !important;
                }

                .show-log-scope .preview-params-sticky.preview-params-sticky--bundle.preview-params-sticky--bundle-open {
                    padding: 0.72rem 0.9rem !important;
                }

                .show-log-scope .preview-params-sticky.preview-params-sticky--bundle.preview-params-sticky--bundle-open .preview-param-items-dropdown-inline > .mb-2 {
                    margin-bottom: 0.2rem !important;
                }

                .show-log-scope .preview-params-sticky .preview-param-row label {
                    font-size: 0.6rem !important;
                    margin-bottom: 0.2rem !important;
                    line-height: 1.1;
                }

                .show-log-scope .preview-params-sticky .preview-param-row .badge {
                    font-size: 0.5rem !important;
                    padding: 0.08rem 0.24rem !important;
                }

                .show-log-scope .preview-params-sticky .preview-param-row .form-control {
                    min-height: 26px;
                    font-size: 0.7rem;
                    padding: 0.12rem 0.28rem !important;
                    line-height: 1.1;
                    display: flex;
                    align-items: center;
                }

                .show-log-scope .preview-params-sticky .preview-param-row .form-control.text-center {
                    justify-content: center;
                }

                .show-log-scope .preview-params-sticky .preview-param-row .input-group-text {
                    min-height: 26px;
                    font-size: 0.56rem !important;
                    padding: 0.1rem 0.24rem !important;
                    line-height: 1.1;
                    display: inline-flex;
                    align-items: center;
                }

                .show-log-scope .bundle-param-section-fields .badge.bg-danger,
                .show-log-scope .bundle-param-section-fields .badge.bg-primary,
                .show-log-scope .bundle-param-section-fields .badge.bg-info,
                .show-log-scope .bundle-param-section-fields .badge.bg-warning,
                .show-log-scope .bundle-param-section-fields .badge.text-white,
                .show-log-scope .bundle-param-section-fields .text-white {
                    color: #ffffff !important;
                }

                .show-log-scope .bundle-param-section-fields .badge.bg-light {
                    color: #475569 !important;
                }

                /* Match preview detail table styling */
                .show-log-scope .table-preview th,
                .show-log-scope .table-preview label,
                .show-log-scope .table-preview button {
                    font-family: 'Nunito', sans-serif !important;
                    color: #ffffff !important;
                    font-weight: 700 !important;
                }

                .show-log-scope .table-preview {
                    width: max-content;
                    min-width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    font-size: 12px;
                    margin: 0;
                    table-layout: auto !important;
                }

                .show-log-scope .table-preview th {
                    background: #891313;
                    color: #ffffff;
                    text-align: center;
                    font-weight: 900;
                    padding: 14px 16px;
                    border: 1px solid #d1d5db;
                    font-size: 14px;
                    letter-spacing: 0.3px;
                    white-space: nowrap;
                    text-shadow: none !important;
                    -webkit-text-stroke: 0 !important;
                }

                .show-log-scope .table-preview td {
                    padding: 14px 16px;
                    border: 1px solid #f1f5f9;
                    vertical-align: top;
                    white-space: nowrap;
                    text-shadow: none !important;
                    -webkit-text-stroke: 0 !important;
                }

                .show-log-scope .table-preview:not(.table-rekap-global) tbody tr {
                    height: 40px;
                }

                .show-log-scope .table-preview:not(.table-rekap-global) tbody td {
                    height: 40px;
                    padding: 8px 10px;
                    vertical-align: middle;
                }

                .show-log-scope .table-preview td.preview-scroll-td {
                    overflow: hidden;
                    white-space: nowrap;
                    text-align: left;
                }

                .show-log-scope .table-preview td.preview-scroll-td:not(.sticky-col-1):not(.sticky-col-2):not(.sticky-col-3) {
                    position: relative;
                }

                .show-log-scope .table-preview td.preview-scroll-td.sticky-col-1,
                .show-log-scope .table-preview td.preview-scroll-td.sticky-col-2,
                .show-log-scope .table-preview td.preview-scroll-td.sticky-col-3 {
                    position: sticky;
                }

                .show-log-scope .table-preview td.preview-store-cell {
                    width: 150px;
                    min-width: 150px;
                    max-width: 150px;
                }

                .show-log-scope .table-preview td.preview-address-cell {
                    width: 200px;
                    min-width: 200px;
                    max-width: 200px;
                }

                .show-log-scope .table-preview th.preview-store-cell {
                    width: 150px;
                    min-width: 150px;
                    max-width: 150px;
                }

                .show-log-scope .table-preview th.preview-address-cell {
                    width: 200px;
                    min-width: 200px;
                    max-width: 200px;
                }

                .show-log-scope .table-preview td.preview-scroll-td.is-scrollable::after {
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

                .show-log-scope .table-preview td.preview-scroll-td.is-scrolled-end::after {
                    opacity: 0;
                }

                .show-log-scope .table-preview .preview-scroll-cell {
                    display: block;
                    width: 100%;
                    overflow-x: auto;
                    overflow-y: hidden;
                    scrollbar-width: none;
                    scrollbar-color: transparent transparent;
                    white-space: nowrap;
                }

                .show-log-scope .table-preview .preview-scroll-cell::-webkit-scrollbar {
                    height: 0;
                }

                .show-log-scope .table-preview tbody tr:last-child td {
                    border-bottom: none;
                }

                .show-log-scope .table-preview tbody tr:hover td {
                    background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
                }

                .show-log-scope .bg-highlight {
                    background: linear-gradient(to right, #f8fafc 0%, #f1f5f9 100%) !important;
                }

                .show-log-scope .bg-highlight-reverse {
                    background: linear-gradient(to left, #f8fafc 0%, #f1f5f9 100%) !important;
                }

                .show-log-scope .text-primary-dark {
                    color: #891313;
                    font-weight: 700;
                }

                .show-log-scope .text-success-dark {
                    color: #059669;
                    font-weight: 700;
                }

                .show-log-scope .sticky-col {
                    position: sticky;
                    left: 0;
                    background-color: white;
                    z-index: 1;
                }

                .show-log-scope .sticky-col-1 {
                    position: sticky;
                    left: 0;
                    background-color: white;
                    z-index: 2;
                    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                    min-width: 105px;
                    max-width: 105px;
                    width: 105px;
                    backface-visibility: hidden;
                    transform: translateZ(0);
                }

                .show-log-scope .sticky-col-2 {
                    position: sticky;
                    left: 105px;
                    background-color: white;
                    z-index: 2;
                    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                    min-width: 60px;
                    max-width: 95px;
                    width: 60px;
                    backface-visibility: hidden;
                    transform: translateZ(0);
                }

                .show-log-scope .sticky-col-3 {
                    position: sticky;
                    left: 165px;
                    background-color: white;
                    z-index: 2;
                    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                    min-width: 120px;
                    max-width: 120px;
                    width: 120px;
                    backface-visibility: hidden;
                    transform: translateZ(0);
                }

                .show-log-scope .table-preview thead th.sticky-col-1,
                .show-log-scope .table-preview thead th.sticky-col-2,
                .show-log-scope .table-preview thead th.sticky-col-3 {
                    background-color: #891313;
                    z-index: 3;
                }

                .show-log-scope .table-preview tbody tr:hover td.sticky-col-1,
                .show-log-scope .table-preview tbody tr:hover td.sticky-col-2,
                .show-log-scope .table-preview tbody tr:hover td.sticky-col-3 {
                    background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
                }

                .show-log-scope .btn-select {
                    background: linear-gradient(135deg, #891313 0%, #a61515 100%);
                    color: #ffffff;
                    border: none;
                    padding: 6px 16px;
                    border-radius: 8px;
                    font-size: 12px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    cursor: pointer;
                    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 2px 4px rgba(137, 19, 19, 0.2);
                }

                .show-log-scope .btn-select:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
                }

                .show-log-scope .group-divider {
                    border-top: 2px solid #891313 !important;
                }

                .show-log-scope .group-end {
                    border-bottom: 3px solid #891313 !important;
                }

                .show-log-scope .group-end td {
                    border-bottom: 3px solid #891313 !important;
                }

                .show-log-scope .rowspan-cell {
                    border-bottom: 3px solid #891313 !important;
                }
            </style>

            <table class="table-preview">
                <thead class="align-top">
                    <tr>
                        <th class="sticky-col-1">Qty<br>/ Pekerjaan</th>
                        <th class="sticky-col-2">Sat.</th>
                        <th class="sticky-col-3">Material</th>
                        <th colspan="4">Detail</th>
                        <th class="preview-store-cell">Toko</th>
                        <th class="preview-address-cell">Alamat</th>
                        <th colspan="2">Harga Beli</th>
                        <th>Biaya<br>/ Material</th>
                        <th>Total Biaya</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Komparasi<br>/ Materal</th>
                    </tr>
                </thead>
                <tbody id="show_log">
                    @php
                        // Helper function: format number without trailing zeros
                        $formatNum = function ($num, $decimals = null) {
                            return \App\Helpers\NumberHelper::format($num);
                        };
                        $formatPlain = function ($num, $maxDecimals = 15) {
                            return \App\Helpers\NumberHelper::formatPlain($num, $maxDecimals, ',', '.');
                        };
                        $formatMoney = function ($num) {
                            return \App\Helpers\NumberHelper::formatFixed($num, 0);
                        };
                        $formatRaw = function ($num, $decimals = 6) {
                            return \App\Helpers\NumberHelper::format($num, $decimals);
                        };

                        $catDetailDisplayParts = [];
                        $catDetailExtraParts = [];
                        $catSubBrand = $materialCalculation->cat ? trim((string) ($materialCalculation->cat->sub_brand ?? '')) : '';
                        $catCode = $materialCalculation->cat ? trim((string) ($materialCalculation->cat->color_code ?? '')) : '';
                        $catColor = $materialCalculation->cat ? trim((string) ($materialCalculation->cat->color_name ?? '')) : '';
                        if ($catSubBrand !== '') {
                            $catDetailDisplayParts[] = $catSubBrand;
                        }
                        if ($catCode !== '') {
                            $catDetailDisplayParts[] = $catCode;
                        }
                        if ($catColor !== '') {
                            $catDetailDisplayParts[] = $catColor;
                        }
                        $catDetailDisplay = !empty($catDetailDisplayParts) ? implode(' - ', $catDetailDisplayParts) : '-';

                        $catPackageUnit = $materialCalculation->cat ? trim((string) ($materialCalculation->cat->package_unit ?? '')) : '';
                        $catVolume = $materialCalculation->cat ? $materialCalculation->cat->volume ?? null : null;
                        $catVolumeUnit = $materialCalculation->cat ? trim((string) ($materialCalculation->cat->volume_unit ?? 'L')) : 'L';
                        if ($catVolumeUnit === '') {
                            $catVolumeUnit = 'L';
                        }
                        $catPackageUnitDisplay = $catPackageUnit !== '' ? $catPackageUnit : '-';
                        $catGrossWeight = $materialCalculation->cat ? $materialCalculation->cat->package_weight_gross ?? null : null;
                        $catGrossDisplay = $catGrossWeight !== null && $catGrossWeight > 0 ? $formatNum($catGrossWeight) : '-';
                        $catDetailExtraParts[] = $catPackageUnitDisplay . ' ( ' . $catGrossDisplay . ' Kg )';
                        if (!empty($catVolume) && $catVolume > 0) {
                            $catDetailExtraParts[] = '( ' . $formatNum($catVolume) . ' ' . $catVolumeUnit . ' )';
                        } else {
                            $catDetailExtraParts[] = '( - ' . $catVolumeUnit . ' )';
                        }
                        if ($materialCalculation->cat && ($materialCalculation->cat->package_weight_net ?? null) !== null) {
                            $catDetailExtraParts[] = 'BB ' . $formatNum($materialCalculation->cat->package_weight_net) . ' Kg';
                        }
                        $catDetailExtra = !empty($catDetailExtraParts) ? implode(' - ', $catDetailExtraParts) : '-';
                        
                        $brick = $materialCalculation->brick;
                        $brickVolume = 0;
                        if ($brick && $brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
                            $brickVolume = ($brick->dimension_length * $brick->dimension_width * $brick->dimension_height) / 1000000;
                        }
                        if ($brickVolume <= 0 && $brick) {
                            $brickVolume = $brick->package_volume ?? 0;
                        }
                        $brickVolumeDisplay = $brickVolume > 0 ? $brickVolume : null;
                        if ($brickVolume <= 0) {
                            $brickVolume = 1;
                        }

                        $cementWeight = $materialCalculation->cement ? $materialCalculation->cement->package_weight_net ?? 0 : 0;
                        if ($cementWeight <= 0) $cementWeight = 1;

                        $catWeight = $materialCalculation->cat ? $materialCalculation->cat->package_weight_net ?? 0 : 0;
                        if ($catWeight <= 0) $catWeight = 1;

                        $ceramicArea = 0;
                        if ($materialCalculation->ceramic && $materialCalculation->ceramic->dimension_length && $materialCalculation->ceramic->dimension_width) {
                            $ceramicArea = ($materialCalculation->ceramic->dimension_length / 100) * ($materialCalculation->ceramic->dimension_width / 100);
                        }
                        if ($ceramicArea <= 0) $ceramicArea = 1;

                        $natWeight = $materialCalculation->nat ? $materialCalculation->nat->package_weight_net ?? 0 : 0;
                        if ($natWeight <= 0) $natWeight = 1;

                        $brickPricePerPiece = $materialCalculation->brick_price_per_piece ?? ($brick->price_per_piece ?? 0);
                        $cementPricePerSak = $materialCalculation->cement_price_per_sak ?? ($materialCalculation->cement->package_price ?? 0);
                        $catPricePerPackage = $materialCalculation->cat_price_per_package ?? ($materialCalculation->cat->purchase_price ?? 0);
                        $ceramicPricePerPackage = $materialCalculation->ceramic_price_per_package ?? ($materialCalculation->ceramic->price_per_package ?? 0);
                        $groutPricePerPackage = $materialCalculation->grout_price_per_package ?? ($materialCalculation->nat->package_price ?? 0);
                        $sandPricePerM3 = $materialCalculation->sand_price_per_m3 ?? 0;
                        if ($sandPricePerM3 <= 0 && $materialCalculation->sand) {
                             $sandPricePerM3 = $materialCalculation->sand->comparison_price_per_m3 ?? 0;
                             if ($sandPricePerM3 <= 0 && ($materialCalculation->sand->package_price ?? 0) > 0 && ($materialCalculation->sand->package_volume ?? 0) > 0) {
                                 $sandPricePerM3 = $materialCalculation->sand->package_price / $materialCalculation->sand->package_volume;
                             }
                        }

                        // Prepare Material Config
                        $bundleMaterialRows = $params['bundle_material_rows'] ?? [];
                        if (is_string($bundleMaterialRows) && trim($bundleMaterialRows) !== '') {
                            $decodedBundleMaterialRows = json_decode($bundleMaterialRows, true);
                            if (!is_array($decodedBundleMaterialRows)) {
                                $decodedBundleMaterialRows = json_decode(
                                    html_entity_decode($bundleMaterialRows, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                                    true,
                                );
                            }
                            $bundleMaterialRows = is_array($decodedBundleMaterialRows)
                                ? $decodedBundleMaterialRows
                                : [];
                        }
                        if (!is_array($bundleMaterialRows)) {
                            $bundleMaterialRows = [];
                        }

                        $materialConfig = [];
                        if (!empty($bundleMaterialRows)) {
                            $hasBundleWaterRow = false;
                            foreach ($bundleMaterialRows as $bundleRowIndex => $bundleRow) {
                                if (!is_array($bundleRow)) {
                                    continue;
                                }

                                $bundleQty = \App\Helpers\NumberHelper::parseNullable($bundleRow['qty'] ?? null);
                                if ($bundleQty === null || $bundleQty <= 0) {
                                    continue;
                                }

                                $bundleMaterialKey = trim((string) ($bundleRow['material_key'] ?? ''));
                                if ($bundleMaterialKey === '') {
                                    $bundleMaterialKey = 'material';
                                }
                                if ($bundleMaterialKey === 'water') {
                                    $hasBundleWaterRow = true;
                                }

                                $bundleDetailValue = \App\Helpers\NumberHelper::parseNullable(
                                    $bundleRow['detail_value'] ?? null,
                                );
                                if ($bundleDetailValue === null || $bundleDetailValue <= 0) {
                                    $bundleDetailValue = 1;
                                }

                                $bundleName = trim((string) ($bundleRow['name'] ?? 'Material'));
                                if ($bundleName === '') {
                                    $bundleName = 'Material';
                                }
                                $bundleUnit = trim((string) ($bundleRow['unit'] ?? '-'));
                                if ($bundleUnit === '') {
                                    $bundleUnit = '-';
                                }
                                $bundleComparisonUnit = trim((string) ($bundleRow['comparison_unit'] ?? $bundleUnit));
                                if ($bundleComparisonUnit === '') {
                                    $bundleComparisonUnit = '-';
                                }

                                $bundleTypeDisplay = trim((string) ($bundleRow['type_display'] ?? '-'));
                                if ($bundleTypeDisplay === '') {
                                    $bundleTypeDisplay = '-';
                                }
                                $bundleBrandDisplay = trim((string) ($bundleRow['brand_display'] ?? '-'));
                                if ($bundleBrandDisplay === '') {
                                    $bundleBrandDisplay = '-';
                                }
                                $bundleStoreDisplay = trim((string) ($bundleRow['store_display'] ?? '-'));
                                if ($bundleStoreDisplay === '') {
                                    $bundleStoreDisplay = '-';
                                }
                                $bundleAddressDisplay = trim((string) ($bundleRow['address_display'] ?? '-'));
                                if ($bundleAddressDisplay === '') {
                                    $bundleAddressDisplay = '-';
                                }
                                $bundleDetailDisplay = trim((string) ($bundleRow['detail_display'] ?? '-'));
                                if ($bundleDetailDisplay === '') {
                                    $bundleDetailDisplay = '-';
                                }
                                $bundleDetailExtra = trim((string) ($bundleRow['detail_extra'] ?? ''));

                                $bundlePackagePrice = (float) ($bundleRow['package_price'] ?? 0);
                                $bundlePackageUnit = trim((string) ($bundleRow['package_unit'] ?? ''));
                                $bundlePricePerUnit = (float) ($bundleRow['price_per_unit'] ?? $bundlePackagePrice);
                                $bundlePriceCalcQty = \App\Helpers\NumberHelper::parseNullable(
                                    $bundleRow['price_calc_qty'] ?? null,
                                );
                                if ($bundlePriceCalcQty === null || $bundlePriceCalcQty <= 0) {
                                    $bundlePriceCalcQty = (float) $bundleQty;
                                }
                                $bundleTotalPrice = (float) ($bundleRow['total_price'] ?? 0);
                                $bundleUnitPrice = (float) ($bundleRow['unit_price'] ?? $bundlePricePerUnit);

                                $materialConfig[$bundleMaterialKey . '_' . $bundleRowIndex] = [
                                    'material_key' => $bundleMaterialKey,
                                    'name' => $bundleName,
                                    'check_field' => $bundleRow['check_field'] ?? $bundleMaterialKey,
                                    'qty' => (float) $bundleQty,
                                    'qty_debug' => $bundleRow['qty_debug'] ?? '',
                                    'unit' => $bundleUnit,
                                    'comparison_unit' => $bundleComparisonUnit,
                                    'detail_value' => (float) $bundleDetailValue,
                                    'detail_value_debug' => $bundleRow['detail_value_debug'] ?? '',
                                    'object' => null,
                                    'type_field' => null,
                                    'type_display' => $bundleTypeDisplay,
                                    'brand_field' => null,
                                    'brand_display' => $bundleBrandDisplay,
                                    'detail_display' => $bundleDetailDisplay,
                                    'detail_extra' => $bundleDetailExtra,
                                    'store_field' => null,
                                    'store_display' => $bundleStoreDisplay,
                                    'address_field' => null,
                                    'address_display' => $bundleAddressDisplay,
                                    'package_price' => $bundlePackagePrice,
                                    'package_unit' => $bundlePackageUnit,
                                    'price_per_unit' => $bundlePricePerUnit,
                                    'price_unit_label' => $bundleRow['price_unit_label'] ?? $bundlePackageUnit,
                                    'price_calc_qty' => (float) $bundlePriceCalcQty,
                                    'price_calc_unit' => $bundleRow['price_calc_unit'] ?? $bundleUnit,
                                    'total_price' => $bundleTotalPrice,
                                    'unit_price' => $bundleUnitPrice,
                                    'unit_price_label' =>
                                        $bundleRow['unit_price_label'] ??
                                        ($bundleRow['price_unit_label'] ?? $bundlePackageUnit),
                                    'is_special' => (bool) ($bundleRow['is_special'] ?? false),
                                ];
                            }

                            if (!$hasBundleWaterRow && ($materialCalculation->water_liters ?? 0) > 0) {
                                $materialConfig['water_fallback'] = [
                                    'material_key' => 'water',
                                    'name' => 'Air',
                                    'check_field' => 'water_liters',
                                    'qty' => (float) ($materialCalculation->water_liters ?? 0),
                                    'qty_debug' => 'Kebutuhan air',
                                    'unit' => 'L',
                                    'comparison_unit' => 'L',
                                    'detail_value' => 1,
                                    'object' => null,
                                    'type_field' => null,
                                    'type_display' => 'Bersih',
                                    'brand_field' => null,
                                    'brand_display' => 'PDAM',
                                    'detail_display' => '',
                                    'detail_extra' => '',
                                    'store_field' => null,
                                    'store_display' => 'Customer',
                                    'address_field' => null,
                                    'address_display' => '-',
                                    'package_price' => 0,
                                    'package_unit' => '',
                                    'total_price' => 0,
                                    'unit_price' => 0,
                                    'unit_price_label' => '',
                                    'is_special' => true,
                                ];
                            }
                        } else {
                            $materialConfig = [
                            'brick' => [
                                'name' => 'Bata',
                                'check_field' => 'brick_quantity',
                                'qty' => $materialCalculation->brick_quantity,
                                'qty_debug' => 'Kebutuhan bata',
                                'unit' => 'Bh',
                                'comparison_unit' => 'M3',
                                'detail_value' => $brickVolume,
                                'detail_value_debug' => 'Rumus: (...) / 1.000.000 = ' . $formatPlain($brickVolume) . ' M3',
                                'object' => $brick,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $brick ? $formatNum($brick->dimension_length) . ' x ' . $formatNum($brick->dimension_width) . ' x ' . $formatNum($brick->dimension_height) . ' cm' : '-',
                                'detail_extra' => $brickVolumeDisplay ? $formatPlain($brickVolumeDisplay) . ' M3' : '-',
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $brick->price_per_piece ?? 0,
                                'package_unit' => 'bh',
                                'price_per_unit' => $brickPricePerPiece,
                                'price_unit_label' => 'bh',
                                'price_calc_qty' => $materialCalculation->brick_quantity,
                                'price_calc_unit' => 'bh',
                                'total_price' => $materialCalculation->brick_total_cost,
                                'unit_price' => $brickPricePerPiece,
                                'unit_price_label' => 'bh',
                            ],
                            'cement' => [
                                'name' => 'Semen',
                                'check_field' => 'cement_quantity_sak',
                                'qty' => $materialCalculation->cement_quantity_sak,
                                'qty_debug' => 'Kebutuhan semen',
                                'unit' => 'Sak',
                                'comparison_unit' => 'Kg',
                                'detail_value' => $cementWeight,
                                'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($cementWeight) . ' Kg',
                                'object' => $materialCalculation->cement,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $materialCalculation->cement ? $materialCalculation->cement->color ?? '-' : '-',
                                'detail_extra' => $materialCalculation->cement
                                    ? ((((($materialCalculation->cement->packageUnit->name ?? null) ?: ($materialCalculation->cement->package_unit ?? 'Sak')) ?: 'Sak') . ' (' . $formatNum($materialCalculation->cement->package_weight_net) . ' Kg)'))
                                    : '-',
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $materialCalculation->cement->package_price ?? 0,
                                'package_unit' => (($materialCalculation->cement->packageUnit->name ?? null) ?: ($materialCalculation->cement->package_unit ?? 'Sak')) ?: 'Sak',
                                'price_per_unit' => $cementPricePerSak,
                                'price_unit_label' => (($materialCalculation->cement->packageUnit->name ?? null) ?: ($materialCalculation->cement->package_unit ?? 'Sak')) ?: 'Sak',
                                'price_calc_qty' => $materialCalculation->cement_quantity_sak,
                                'price_calc_unit' => 'Sak',
                                'total_price' => $materialCalculation->cement_total_cost,
                                'unit_price' => $cementPricePerSak,
                                'unit_price_label' => (($materialCalculation->cement->packageUnit->name ?? null) ?: ($materialCalculation->cement->package_unit ?? 'Sak')) ?: 'Sak',
                            ],
                            'sand' => [
                                'name' => 'Pasir',
                                'check_field' => 'sand_m3',
                                'qty' => $materialCalculation->sand_m3,
                                'qty_debug' => 'Kebutuhan pasir',
                                'unit' => 'M3',
                                'comparison_unit' => 'M3',
                                'detail_value' => $materialCalculation->sand && $materialCalculation->sand->package_volume > 0 ? $materialCalculation->sand->package_volume : 1,
                                'detail_value_debug' => $materialCalculation->sand ? 'Volume per kemasan: ' . $formatNum($materialCalculation->sand->package_volume ?? 0) . ' M3' : '-',
                                'object' => $materialCalculation->sand,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $materialCalculation->sand ? $materialCalculation->sand->package_unit ?? '-' : '-',
                                'detail_extra' => $materialCalculation->sand ? ($materialCalculation->sand->package_volume ? $formatNum($materialCalculation->sand->package_volume) . ' M3' : '-') : '-',
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $materialCalculation->sand->package_price ?? 0,
                                'package_unit' => $materialCalculation->sand->package_unit ?? 'Karung',
                                'price_per_unit' => $sandPricePerM3,
                                'price_unit_label' => 'M3',
                                'price_calc_qty' => $materialCalculation->sand_m3,
                                'price_calc_unit' => 'M3',
                                'total_price' => $materialCalculation->sand_total_cost,
                                'unit_price' => $sandPricePerM3,
                                'unit_price_label' => $materialCalculation->sand->package_unit ?? 'Karung',
                            ],
                            'cat' => [
                                'name' => 'Cat',
                                'check_field' => 'cat_quantity',
                                'qty' => $materialCalculation->cat_quantity,
                                'qty_debug' => 'Kebutuhan cat',
                                'unit' => $materialCalculation->cat->package_unit ?? 'Kmsn',
                                'comparison_unit' => 'Kg',
                                'detail_value' => $catWeight,
                                'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($catWeight) . ' Kg',
                                'object' => $materialCalculation->cat,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $catDetailDisplay,
                                'detail_extra' => $catDetailExtra,
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $materialCalculation->cat->purchase_price ?? 0,
                                'package_unit' => $materialCalculation->cat->package_unit ?? 'Galon',
                                'price_per_unit' => $catPricePerPackage,
                                'price_unit_label' => $materialCalculation->cat->package_unit ?? 'Galon',
                                'price_calc_qty' => $materialCalculation->cat_quantity,
                                'price_calc_unit' => $materialCalculation->cat->package_unit ?? 'Galon',
                                'total_price' => $materialCalculation->cat_total_cost,
                                'unit_price' => $catPricePerPackage,
                                'unit_price_label' => $materialCalculation->cat->package_unit ?? 'Galon',
                            ],
                            'ceramic' => [
                                'name' => 'Keramik',
                                'check_field' => 'ceramic_quantity',
                                'qty' => $materialCalculation->ceramic_quantity,
                                'qty_debug' => 'Kebutuhan keramik',
                                'unit' => 'Bh',
                                'comparison_unit' => 'M2',
                                'detail_value' => $ceramicArea,
                                'detail_value_debug' => '-',
                                'object' => $materialCalculation->ceramic,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $materialCalculation->ceramic ? $materialCalculation->ceramic->color ?? '-' : '-',
                                'detail_extra' => $materialCalculation->ceramic ? $formatNum($materialCalculation->ceramic->dimension_length) . 'x' . $formatNum($materialCalculation->ceramic->dimension_width) . ' cm' : '-',
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $materialCalculation->ceramic->price_per_package ?? 0,
                                'package_unit' => 'Dus',
                                'price_per_unit' => $ceramicPricePerPackage,
                                'price_unit_label' => 'Dus',
                                'price_calc_qty' => $materialCalculation->ceramic_quantity / ($materialCalculation->ceramic->pieces_per_package ?? 1), // Approx packages
                                'price_calc_unit' => 'Dus',
                                'total_price' => $materialCalculation->ceramic_total_cost,
                                'unit_price' => $ceramicPricePerPackage,
                                'unit_price_label' => 'Dus',
                            ],
                            'nat' => [
                                'name' => 'Nat',
                                'check_field' => 'nat_quantity',
                                'qty' => $materialCalculation->nat_quantity,
                                'qty_debug' => 'Kebutuhan nat',
                                'unit' => 'Bks',
                                'comparison_unit' => 'Kg',
                                'detail_value' => $natWeight,
                                'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($natWeight) . ' Kg',
                                'object' => $materialCalculation->nat,
                                'type_field' => 'type',
                                'brand_field' => 'brand',
                                'detail_display' => $materialCalculation->nat ? $materialCalculation->nat->color ?? 'Nat' : 'Nat',
                                'detail_extra' => $materialCalculation->nat ? $formatNum($materialCalculation->nat->package_weight_net) . ' Kg' : '-',
                                'store_field' => 'store',
                                'address_field' => 'address',
                                'package_price' => $materialCalculation->nat->package_price ?? 0,
                                'package_unit' => $materialCalculation->nat->package_unit ?? 'Bks',
                                'price_per_unit' => $groutPricePerPackage,
                                'price_unit_label' => $materialCalculation->nat->package_unit ?? 'Bks',
                                'price_calc_qty' => $materialCalculation->nat_quantity,
                                'price_calc_unit' => 'Bks',
                                'total_price' => $materialCalculation->nat_total_cost,
                                'unit_price' => $groutPricePerPackage,
                                'unit_price_label' => $materialCalculation->nat->package_unit ?? 'Bks',
                            ],
                            'water' => [
                                'name' => 'Air',
                                'check_field' => 'water_liters',
                                'qty' => $materialCalculation->water_liters,
                                'qty_debug' => 'Kebutuhan air',
                                'unit' => 'L',
                                'comparison_unit' => 'L',
                                'detail_value' => 1,
                                'object' => null,
                                'type_field' => null,
                                'type_display' => 'Bersih',
                                'brand_field' => null,
                                'brand_display' => 'PDAM',
                                'detail_display' => '',
                                'detail_extra' => '',
                                'store_field' => null,
                                'store_display' => 'Customer',
                                'address_field' => null,
                                'address_display' => '-',
                                'package_price' => 0,
                                'package_unit' => '',
                                'total_price' => 0,
                                'unit_price' => 0,
                                'unit_price_label' => '',
                                'is_special' => true,
                            ],
                            ];
                        }

                        $visibleMaterials = array_filter($materialConfig, function ($mat) {
                            return isset($mat['qty']) && $mat['qty'] > 0;
                        });

                        $rowCount = count($visibleMaterials);
                        $matIndex = 0;
                        $areaForCost = $materialCalculation->wall_area > 0 ? $materialCalculation->wall_area : 1;
                    @endphp

                    @foreach ($visibleMaterials as $matKey => $mat)
                        @php
                            $matIndex++;
                            $isFirstMaterial = $matIndex === 1;
                            $isLastMaterial = $matIndex === count($visibleMaterials);
                            $materialTypeKey = (string) ($mat['material_key'] ?? $matKey);
                            
                            $pricePerUnit = $mat['price_per_unit'] ?? ($mat['package_price'] ?? 0);
                            $priceUnitLabel = $mat['price_unit_label'] ?? ($mat['package_unit'] ?? '');
                            $priceCalcQty = $mat['price_calc_qty'] ?? ($mat['qty'] ?? 0);
                            $hargaKomparasi = round((float) ($mat['total_price'] ?? 0), 0);
                            if ($hargaKomparasi <= 0 && !(isset($mat['is_special']) && $mat['is_special'])) {
                                $hargaKomparasi = round((float) (($pricePerUnit ?? 0) * ($priceCalcQty ?? 0)), 0);
                            }
                            $comparisonUnit = $mat['comparison_unit'] ?? ($mat['unit'] ?? '');
                            $detailValue = $mat['detail_value'] ?? 1;

                            $qtyTitleParts = [];
                            if (!empty($mat['qty_debug'])) $qtyTitleParts[] = $mat['qty_debug'];
                            $qtyTitleParts[] = 'Nilai tampil: ' . $formatNum($mat['qty']) . ' ' . ($mat['unit'] ?? '');
                            $qtyTitle = implode(' | ', $qtyTitleParts);

                            $detailTitleParts = [];
                            if (!empty($mat['detail_value_debug'])) $detailTitleParts[] = $mat['detail_value_debug'];
                            if (!empty($mat['detail_extra_debug'])) $detailTitleParts[] = $mat['detail_extra_debug'];
                            if (!empty($mat['detail_extra'])) $detailTitleParts[] = 'Nilai tampil: ' . $mat['detail_extra'];
                            $detailTitle = implode(' | ', $detailTitleParts);

                            $packagePriceTitleParts = [];
                            $packagePriceTitleParts[] = 'Nilai tampil: Rp ' . $formatMoney($mat['package_price']) . ' / ' . $mat['package_unit'];
                            $packagePriceTitle = implode(' | ', $packagePriceTitleParts);
                        @endphp
                        <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                            {{-- Column 1-3: Qty, Unit, Material Name --}}
                            <td class="text-end fw-bold sticky-col-1 preview-scroll-td" style="border-right: none;" title="{{ $qtyTitle }}">
                                <div class="preview-scroll-cell">@formatResult($mat['qty'])</div>
                            </td>
                            <td class="text-start sticky-col-2" style="border-left: none; border-right: none;">
                                {{ $mat['unit'] }}
                            </td>
                            <td class="fw-bold sticky-col-3" style="border-left: none;">{{ $mat['name'] }}</td>

                            {{-- Column 4-9: Material Details --}}
                            <td class="text-muted" style="border-right: none;">
                                {{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}
                            </td>
                            <td class="fw-bold" style="border-left: none; border-right: none;">
                                {{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}
                            </td>
                            <td class="{{ $materialTypeKey === 'brick' ? 'text-start text-nowrap' : '' }}" style="border-left: none; border-right: none;">
                                {{ $mat['detail_display'] }}
                            </td>
                            <td class="{{ $materialTypeKey === 'cement' || $materialTypeKey === 'sand' || $materialTypeKey === 'brick' ? 'text-start text-nowrap fw-bold' : '' }} {{ $materialTypeKey === 'brick' ? 'preview-scroll-td' : '' }}" title="{{ $detailTitle }}" style="border-left: none;">
                                @if ($materialTypeKey === 'brick')
                                    <div class="preview-scroll-cell">{{ $mat['detail_extra'] ?? '' }}</div>
                                @else
                                    {{ $mat['detail_extra'] ?? '' }}
                                @endif
                            </td>
                            <td class="preview-scroll-td preview-store-cell">
                                <div class="preview-scroll-cell">
                                    {{ $mat['store_display'] ?? ($mat['object']->{$mat['store_field']} ?? '-') }}
                                </div>
                            </td>
                            <td class="preview-scroll-td preview-address-cell small text-muted">
                                <div class="preview-scroll-cell">
                                    {{ $mat['address_display'] ?? ($mat['object']->{$mat['address_field']} ?? '-') }}
                                </div>
                            </td>

                            {{-- Column 10-11: Package Price --}}
                            @if (isset($mat['is_special']) && $mat['is_special'])
                                <td class="text-center text-muted" style="border-right: none;">-</td>
                                <td style="border-left: none;"></td>
                            @else
                                <td class="text-nowrap fw-bold" title="{{ $packagePriceTitle }}" style="border-right: none;">
                                    <div class="d-flex justify-content-between" style="width: 100px;">
                                        <span>Rp</span>
                                        <span>{{ $formatMoney($mat['package_price']) }}</span>
                                    </div>
                                </td>
                                <td class="text-muted text-nowrap ps-1" style="border-left: none;">/ {{ $mat['package_unit'] }}</td>
                            @endif

                            {{-- Column 12: Total Price (Harga Komparasi) --}}
                            @if (isset($mat['is_special']) && $mat['is_special'])
                                <td class="text-center text-muted">-</td>
                            @else
                                <td class="text-nowrap">
                                    <div class="d-flex justify-content-between w-100">
                                        <span>Rp</span>
                                        <span>{{ $formatMoney($hargaKomparasi) }}</span>
                                    </div>
                                </td>
                            @endif

                            {{-- Column 13-15: Rowspan columns --}}
                            @if ($isFirstMaterial)
                                <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell">
                                    <div class="d-flex justify-content-between w-100">
                                        <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                        <span class="text-success-dark" style="font-size: 15px;">@price($materialCalculation->total_material_cost)</span>
                                    </div>
                                </td>
                                <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell" style="border-right: none;">
                                    <div class="d-flex justify-content-between w-100">
                                        <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                        <span class="text-primary-dark" style="font-size: 14px;">@price($costPerM2)</span>
                                    </div>
                                </td>
                                <td rowspan="{{ $rowCount }}" class="bg-highlight-reverse align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px; border-left: none;">/ M2</td>
                            @endif

                            {{-- Column 16-17: Harga Beli Aktual --}}
                            @if (isset($mat['is_special']) && $mat['is_special'])
                                <td class="text-center text-muted" style="border-right: none;">-</td>
                                <td style="border-left: none;"></td>
                            @else
                                @php
                                    $normalizedQtyValue = (float) ($mat['qty'] ?? 0);
                                    $totalPriceValue = round((float) $hargaKomparasi, 0);
                                    $normalizedDetailValue = (float) $detailValue;
                                    
                                    if ($materialTypeKey === 'sand') {
                                        $actualBuyPrice = $normalizedQtyValue > 0 ? $totalPriceValue / $normalizedQtyValue : 0;
                                    } else {
                                        $actualBuyPrice = ($normalizedQtyValue > 0 && $normalizedDetailValue > 0) ? $totalPriceValue / $normalizedQtyValue / $normalizedDetailValue : 0;
                                    }
                                @endphp
                                <td class="text-nowrap" style="border-right: none;">
                                    <div class="d-flex justify-content-between w-100">
                                        <span>Rp</span>
                                        <span>{{ $formatMoney($actualBuyPrice) }}</span>
                                    </div>
                                </td>
                                <td class="text-muted text-nowrap ps-1" style="border-left: none;">/ {{ $comparisonUnit }}</td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>
            </table>
    </div>
    </div>
    </div>

</div>

<style>
    @media print {
        /* Atur page menjadi landscape */
        @page {
            size: landscape;
            margin: 8mm;
        }

        /* Sembunyikan navigation dan buttons - PENTING! */
        .nav,
        nav,
        .navbar,
        button,
        .btn,
        a[href*="kembali"],
        a[href*="edit"],
        a.btn-cancel,
        a.btn-action {
            display: none !important;
        }

        /* Reset body untuk print */
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Container simple */
        .container,
        .container-fluid {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Card tanpa styling */
        .card {
            box-shadow: none !important;
            border: none !important;
            padding: 8px !important;
            margin: 0 !important;
        }

        /* Table responsive - visible all */
        .table-responsive {
            overflow: visible !important;
        }

        /* Table - Simple & Clean */
        .table-preview {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important;
        }

        .table-preview thead {
            display: table-header-group !important;
        }

        .table-preview tbody {
            display: table-row-group !important;
        }

        .table-preview th {
            background: #891313 !important;
            color: white !important;
            padding: 6px 4px !important;
            font-size: 9px !important;
            border: 1px solid #666 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-preview td {
            padding: 6px 4px !important;
            font-size: 9px !important;
            border: 1px solid #ddd !important;
            vertical-align: middle !important;
        }

        /* Warna tetap muncul */
        .bg-highlight {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .text-success-dark {
            color: #059669 !important;
        }

        .text-primary-dark {
            color: #891313 !important;
        }

        /* Header */
        h2 {
            font-size: 14px !important;
            margin: 8px 0 !important;
        }

        /* Hide hover effects */
        .table-preview tbody tr:hover td {
            background: transparent !important;
        }

        /* Prevent page break di tengah row */
        tr {
            page-break-inside: avoid;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateScrollIndicators() {
            const cells = document.querySelectorAll('.table-preview .preview-scroll-td');
            cells.forEach(function (cell) {
                const scroller = cell.querySelector('.preview-scroll-cell');
                if (!scroller) return;
                const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
                cell.classList.toggle('is-scrollable', isScrollable);
                const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
                cell.classList.toggle('is-scrolled-end', isScrollable && atEnd);
            });
        }

        function bindScrollHandlers() {
            const cells = document.querySelectorAll('.table-preview .preview-scroll-td');
            cells.forEach(function (cell) {
                const scroller = cell.querySelector('.preview-scroll-cell');
                if (!scroller || scroller.__previewScrollBound) return;
                scroller.__previewScrollBound = true;
                scroller.addEventListener('scroll', updateScrollIndicators, {
                    passive: true,
                });
            });
        }

        function refreshIndicators() {
            updateScrollIndicators();
            bindScrollHandlers();
            requestAnimationFrame(updateScrollIndicators);
            setTimeout(updateScrollIndicators, 60);
        }

        function bindBundleParamDropdowns() {
            const wrappers = document.querySelectorAll(
                '.show-log-scope .preview-param-items-dropdown.preview-param-items-dropdown-inline',
            );

            wrappers.forEach(function (wrapper) {
                const toggleBtn = wrapper.querySelector('[data-param-dropdown-toggle="true"]');
                const menu = wrapper.querySelector('.bundle-param-dropdown-menu');
                if (!toggleBtn || !menu || toggleBtn.__bundleDropdownBound) return;

                const stickyCard = wrapper.closest('.preview-params-sticky--bundle');
                const setOpen = function (open) {
                    menu.classList.toggle('show', open);
                    wrapper.classList.toggle('show', open);
                    toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (stickyCard) {
                        stickyCard.classList.toggle('preview-params-sticky--bundle-open', open);
                    }
                };

                setOpen(false);
                toggleBtn.__bundleDropdownBound = true;

                toggleBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    const isOpen = menu.classList.contains('show');
                    setOpen(!isOpen);
                });

                document.addEventListener('click', function (event) {
                    if (!wrapper.contains(event.target)) {
                        setOpen(false);
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        setOpen(false);
                    }
                });
            });
        }

        refreshIndicators();
        bindBundleParamDropdowns();
        window.addEventListener('resize', refreshIndicators);
    });
</script>
@endsection
