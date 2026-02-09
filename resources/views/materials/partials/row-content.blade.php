@php
    $showStoreInfo = $showStoreInfo ?? true;
@endphp
@if($material['type'] == 'brick')
    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
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
    <td class="border-right-none brick-scroll-td" style="text-align: right; width: 80px; min-width: 80px; font-size: 12px;">
        @if(!is_null($item->package_volume))
            <div class="brick-scroll-cell" style="max-width: 80px; width: 100%; white-space: nowrap;">
                {{ \App\Helpers\NumberHelper::formatPlain($item->package_volume) }}
            </div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">M3</td>
    @if($showStoreInfo)
    <td class="brick-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="brick-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
    </td>
    <td class="brick-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="brick-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
    </td>
    @endif
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
    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="cat-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
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
    <td class="border-right-none cat-scroll-td" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->volume)
            <div class="cat-scroll-cell" style="max-width: 60px; width: 100%; white-space: nowrap;">@format($item->volume)</div>
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
    @if($showStoreInfo)
    <td class="cat-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="cat-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
    </td>
    <td class="cat-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="cat-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
    </td>
    @endif
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

@elseif(in_array($material['type'], ['cement', 'nat']))
    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="cement-sticky-col" style="text-align: left;">
        {{ $material['type'] === 'nat' ? ($item->type ?? $item->nat_name ?? '-') : ($item->type ?? '-') }}
    </td>
    <td class="material-brand-cell cement-sticky-col cement-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
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
    @if($showStoreInfo)
    <td class="cement-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="cement-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
    </td>
    <td class="cement-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="cement-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
    </td>
    @endif
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
    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
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
    <td class="border-right-none sand-scroll-td" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
        @if($item->package_volume)
            <div class="sand-scroll-cell" style="max-width: 60px; width: 100%; white-space: nowrap;">
                {{ \App\Helpers\NumberHelper::formatPlain($item->package_volume) }}
            </div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M3</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
    </td>
    @endif
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
    <td class="{{ $stickyClass }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center;">
        {{ $rowNumber }}
    </td>
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
    <td class="material-brand-cell ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
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
    @if($showStoreInfo)
    <td class="ceramic-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="ceramic-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
    </td>
    <td class="ceramic-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="ceramic-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
    </td>
    @endif
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
