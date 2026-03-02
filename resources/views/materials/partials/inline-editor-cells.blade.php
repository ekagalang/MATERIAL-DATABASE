@php
    $showStoreInfo = $showStoreInfo ?? true;
    $showActions = $showActions ?? true;
    $inlinePackageUnits = $inlinePackageUnits ?? [];
    $packageUnitOptions = collect($inlinePackageUnits[$material['type']] ?? []);
    $packageUnitCodes = $packageUnitOptions
        ->pluck('code')
        ->filter(fn($value) => is_string($value) && trim($value) !== '')
        ->map(fn($value) => trim($value))
        ->unique()
        ->implode('|');
@endphp

@if($material['type'] == 'brick')
    <td class="material-inline-row-no brick-sticky-col col-no" style="text-align:center;">+</td>
    <td class="brick-sticky-col col-type"><input autocomplete="off" class="material-inline-input" name="type" data-inline-field="type" form="{{ $inlineFormId }}"></td>
    <td class="brick-sticky-col col-brand brick-sticky-edge"><input autocomplete="off" class="material-inline-input" name="brand" data-inline-field="brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="form" data-inline-field="form" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_length" data-inline-field="dimension_length" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_width" data-inline-field="dimension_width" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_height" data-inline-field="dimension_height" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="package_volume" data-inline-field="package_volume" form="{{ $inlineFormId }}"></td>
    <td style="text-align:center;">M3</td>
    <td>
        <input
            autocomplete="off"
            class="material-inline-input"
            name="package_type"
            data-inline-field="package_type"
            data-inline-static-values="eceran|kubik"
            form="{{ $inlineFormId }}">
    </td>
    <td style="text-align:right;" data-inline-brick-package-count>( -</td>
    <td style="text-align:left;" data-inline-brick-package-unit>Bh )</td>
    @if($showStoreInfo)
        <td><input autocomplete="off" class="material-inline-input" name="store" data-inline-field="store" form="{{ $inlineFormId }}"></td>
        <td><input autocomplete="off" class="material-inline-input" name="address" data-inline-field="address" form="{{ $inlineFormId }}"></td>
    @endif
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="price_per_piece" data-inline-field="price_per_piece" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;" data-inline-brick-price-unit>/ Bh</td>
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="comparison_price_per_m3" data-inline-field="comparison_price_per_m3" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ M3</td>

@elseif($material['type'] == 'cat')
    <td class="material-inline-row-no cat-sticky-col col-no" style="text-align:center;">+</td>
    <td class="cat-sticky-col col-type"><input autocomplete="off" class="material-inline-input" name="type" data-inline-field="type" form="{{ $inlineFormId }}"></td>
    <td class="cat-sticky-col col-brand cat-sticky-edge"><input autocomplete="off" class="material-inline-input" name="brand" data-inline-field="brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="sub_brand" data-inline-field="sub_brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="color_code" data-inline-field="color_code" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="color_name" data-inline-field="color_name" form="{{ $inlineFormId }}"></td>
    <td>
        <input
            autocomplete="off"
            class="material-inline-input"
            name="package_unit"
            data-inline-field="package_unit"
            data-inline-static-values="{{ $packageUnitCodes }}"
            form="{{ $inlineFormId }}">
    </td>
    <td><input autocomplete="off" class="material-inline-input" name="package_weight_gross" data-inline-field="package_weight_gross" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">Kg )</td>
    <td><input autocomplete="off" class="material-inline-input" name="volume" data-inline-field="volume" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">L</td>
    <td><input autocomplete="off" class="material-inline-input" name="package_weight_net" data-inline-field="package_weight_net" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">Kg</td>
    @if($showStoreInfo)
        <td><input autocomplete="off" class="material-inline-input" name="store" data-inline-field="store" form="{{ $inlineFormId }}"></td>
        <td><input autocomplete="off" class="material-inline-input" name="address" data-inline-field="address" form="{{ $inlineFormId }}"></td>
    @endif
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="purchase_price" data-inline-field="purchase_price" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Unit</td>
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="comparison_price_per_kg" data-inline-field="comparison_price_per_kg" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Kg</td>

@elseif(in_array($material['type'], ['cement', 'nat']))
    <td class="material-inline-row-no cement-sticky-col" style="text-align:center;">+</td>
    <td class="cement-sticky-col"><input autocomplete="off" class="material-inline-input" name="type" data-inline-field="type" form="{{ $inlineFormId }}"></td>
    <td class="cement-sticky-col cement-sticky-edge"><input autocomplete="off" class="material-inline-input" name="brand" data-inline-field="brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="sub_brand" data-inline-field="sub_brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="code" data-inline-field="code" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="color" data-inline-field="color" form="{{ $inlineFormId }}"></td>
    <td>
        <input
            autocomplete="off"
            class="material-inline-input"
            name="package_unit"
            data-inline-field="package_unit"
            data-inline-static-values="{{ $packageUnitCodes }}"
            form="{{ $inlineFormId }}">
    </td>
    <td><input autocomplete="off" class="material-inline-input" name="package_weight_net" data-inline-field="package_weight_net" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">Kg</td>
    @if($showStoreInfo)
        <td><input autocomplete="off" class="material-inline-input" name="store" data-inline-field="store" form="{{ $inlineFormId }}"></td>
        <td><input autocomplete="off" class="material-inline-input" name="address" data-inline-field="address" form="{{ $inlineFormId }}"></td>
    @endif
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="package_price" data-inline-field="package_price" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Unit</td>
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="comparison_price_per_kg" data-inline-field="comparison_price_per_kg" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Kg</td>

@elseif($material['type'] == 'sand')
    <td class="material-inline-row-no" style="text-align:center;">+</td>
    <td><input autocomplete="off" class="material-inline-input" name="type" data-inline-field="type" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="brand" data-inline-field="brand" form="{{ $inlineFormId }}"></td>
    <td>
        <input
            autocomplete="off"
            class="material-inline-input"
            name="package_unit"
            data-inline-field="package_unit"
            data-inline-static-values="{{ $packageUnitCodes }}"
            form="{{ $inlineFormId }}">
    </td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_length" data-inline-field="dimension_length" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_width" data-inline-field="dimension_width" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell"><input autocomplete="off" class="material-inline-input" name="dimension_height" data-inline-field="dimension_height" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="package_volume" data-inline-field="package_volume" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">M3</td>
    @if($showStoreInfo)
        <td><input autocomplete="off" class="material-inline-input" name="store" data-inline-field="store" form="{{ $inlineFormId }}"></td>
        <td><input autocomplete="off" class="material-inline-input" name="address" data-inline-field="address" form="{{ $inlineFormId }}"></td>
    @endif
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="package_price" data-inline-field="package_price" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Unit</td>
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="comparison_price_per_m3" data-inline-field="comparison_price_per_m3" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ M3</td>

@elseif($material['type'] == 'ceramic')
    <td class="material-inline-row-no ceramic-sticky-col col-no" style="text-align:center;">+</td>
    <td class="ceramic-sticky-col col-type"><input autocomplete="off" class="material-inline-input" name="type" data-inline-field="type" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell ceramic-sticky-col col-dim-p border-right-none"><input autocomplete="off" class="material-inline-input" name="dimension_length" data-inline-field="dimension_length" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell ceramic-sticky-col col-dim-l border-left-none border-right-none"><input autocomplete="off" class="material-inline-input" name="dimension_width" data-inline-field="dimension_width" form="{{ $inlineFormId }}"></td>
    <td class="dim-cell ceramic-sticky-col col-dim-t border-left-none"><input autocomplete="off" class="material-inline-input" name="dimension_thickness" data-inline-field="dimension_thickness" form="{{ $inlineFormId }}"></td>
    <td class="ceramic-sticky-col col-brand ceramic-sticky-edge"><input autocomplete="off" class="material-inline-input" name="brand" data-inline-field="brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="sub_brand" data-inline-field="sub_brand" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="surface" data-inline-field="surface" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="code" data-inline-field="code" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="color" data-inline-field="color" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="form" data-inline-field="form" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="packaging" data-inline-field="packaging" form="{{ $inlineFormId }}"></td>
    <td><input autocomplete="off" class="material-inline-input" name="pieces_per_package" data-inline-field="pieces_per_package" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">Lbr )</td>
    <td><input autocomplete="off" class="material-inline-input" name="coverage_per_package" data-inline-field="coverage_per_package" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">M2</td>
    @if($showStoreInfo)
        <td><input autocomplete="off" class="material-inline-input" name="store" data-inline-field="store" form="{{ $inlineFormId }}"></td>
        <td><input autocomplete="off" class="material-inline-input" name="address" data-inline-field="address" form="{{ $inlineFormId }}"></td>
    @endif
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="price_per_package" data-inline-field="price_per_package" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ Unit</td>
    <td style="text-align:right;">Rp</td>
    <td><input autocomplete="off" class="material-inline-input" name="comparison_price_per_m2" data-inline-field="comparison_price_per_m2" form="{{ $inlineFormId }}"></td>
    <td style="text-align:left;">/ M2</td>
@endif

@if($showActions)
    <td class="text-center action-cell">
        <div class="btn-group-compact">
            <button type="button"
                class="btn btn-action material-inline-photo-trigger"
                data-inline-photo-trigger
                title="Upload foto">
                <i class="bi bi-camera"></i>
            </button>
            <input type="file"
                name="photo"
                accept="image/*"
                data-inline-field="photo"
                data-inline-photo-input
                form="{{ $inlineFormId }}"
                style="display:none;">
            <button type="submit" form="{{ $inlineFormId }}" class="btn btn-action btn-primary-glossy" title="Simpan">
                <i class="bi bi-check2"></i>
            </button>
            <button type="button" class="btn btn-action btn-warning" data-inline-close title="Batal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </td>
@endif
