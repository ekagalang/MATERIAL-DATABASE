@php
    $materialMeta = [
        'brick' => [
            'label' => 'Bata',
            'icon' => 'bi bi-bricks text-success',
            'selectClass' => 'select-green',
            'placeholder' => '-- Pilih Bata --',
        ],
        'cement' => [
            'label' => 'Semen',
            'icon' => 'bi bi-box-seam text-danger',
            'selectClass' => 'select-orange',
            'placeholder' => '-- Pilih Semen --',
        ],
        'sand' => [
            'label' => 'Pasir',
            'icon' => 'bi bi-bucket text-secondary',
            'selectClass' => 'select-gray',
            'placeholder' => '-- Pilih Pasir --',
        ],
        'cat' => [
            'label' => 'Cat',
            'icon' => 'bi bi-palette text-info',
            'selectClass' => 'select-blue',
            'placeholder' => '-- Pilih Cat --',
        ],
        'ceramic' => [
            'label' => 'Keramik',
            'icon' => 'bi bi-grid-3x3-gap text-primary',
            'selectClass' => 'select-pink',
            'placeholder' => '-- Pilih Keramik --',
        ],
        'nat' => [
            'label' => 'Nat',
            'icon' => 'bi bi-droplet-half text-warning',
            'selectClass' => 'select-gray-light',
            'placeholder' => '-- Pilih Nat --',
        ],
    ];

    $formulaMaterials = [];
    if (!empty($workTypeCode ?? null)) {
        $formula = collect($formulas ?? [])->firstWhere('code', $workTypeCode);
        $formulaMaterials = $formula['materials'] ?? [];
    }
    $visibleMaterials = !empty($formulaMaterials) ? $formulaMaterials : array_keys($materialMeta);
    $resolvedWorkType = $workTypeCode ?? ($rec ? $rec->work_type : null);
    if ($resolvedWorkType === 'grout_tile') {
        $visibleMaterials = array_values(array_diff($visibleMaterials, ['ceramic']));
    }
@endphp

<div class="recommendation-card" data-index="{{ $index }}">
    <div class="card-header-row">
        <span class="card-title-text">
            <i class="bi bi-bookmark-star-fill text-warning me-2"></i>Rekomendasi #{{ is_numeric($index) ? $index + 1 : 'NEW' }}
        </span>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    </div>

    {{-- WORK TYPE HIDDEN INPUT (fixed per accordion) --}}
    <input type="hidden"
           name="recommendations[{{ $index }}][work_type]"
           value="{{ $workTypeCode ?? ($rec ? $rec->work_type : 'brick_half') }}">

    <div class="material-grid">
        @foreach($materialMeta as $materialKey => $material)
            @php
                $isVisible = in_array($materialKey, $visibleMaterials, true);
                $selectedValue = $rec ? ($rec->{$materialKey . '_id'} ?? null) : null;
            @endphp
            <div class="material-section" data-material-type="{{ $materialKey }}" style="{{ $isVisible ? '' : 'display:none;' }}">
                <div class="section-header">
                    <i class="{{ $material['icon'] }}"></i> {{ $material['label'] }}
                </div>

                <div class="form-group">
                    <label>Produk</label>
                    <div class="input-wrapper">
                        <select name="recommendations[{{ $index }}][{{ $materialKey }}_id]"
                                class="form-select {{ $material['selectClass'] }} material-select"
                                data-material-type="{{ $materialKey }}"
                                data-selected="{{ $selectedValue }}">
                            <option value="">{{ $material['placeholder'] }}</option>
                            {{-- Populated by JS --}}
                        </select>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
