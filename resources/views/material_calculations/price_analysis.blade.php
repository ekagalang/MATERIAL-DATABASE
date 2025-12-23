@extends('layouts.app')

@section('title', 'Analisa Harga Material')

@section('content')

{{-- CONFIGURATION CARD --}}
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('price-analysis.calculate') }}" method="POST">
            @csrf
            
            {{-- ROW FLEX CUSTOM --}}
            <div class="d-md-flex align-items-end mb-3 gap-2">
                <div style="flex: 0 0 55%; max-width: 50%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary" style="font-size: 0.75rem;">
                        <i class="bi bi-briefcase me-1"></i>Jenis Item Pekerjaan
                    </label>
                    <select name="formula_code" class="form-select fw-bold border-secondary text-dark">
                        @foreach($formulas as $formula)
                            <option value="{{ $formula['code'] }}" {{ ($inputs['formula_code'] ?? '') == $formula['code'] ? 'selected' : '' }}>
                                {{ $formula['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 0 0 8%; max-width: 8%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">TEBAL</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="mortar_thickness" class="form-control fw-bold text-center px-1" value="{{ $inputs['mortar_thickness'] ?? 2.0 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">cm</span>
                    </div>
                </div>
                <div style="flex: 0 0 8%; max-width: 8%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">PANJANG</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" id="input_p" name="wall_length" class="form-control fw-bold text-center px-1" placeholder="0" value="{{ $inputs['wall_length'] ?? 1 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">M</span>
                    </div>
                </div>
                <div style="flex: 0 0 8%; max-width: 8%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">TINGGI</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" id="input_t" name="wall_height" class="form-control fw-bold text-center px-1" placeholder="0" value="{{ $inputs['wall_height'] ?? 1 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">M</span>
                    </div>
                </div>
                <div style="flex: 0 0 8%; max-width: 8%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-danger text-white border border-danger">LUAS</span>
                    </label>
                    <div class="input-group">
                        <input type="text" id="output_area" class="form-control fw-bold text-center bg-light text-primary px-1" readonly value="{{ isset($inputs['wall_area']) ? number_format($inputs['wall_area'], 2) : '1.00' }}">
                        <span class="input-group-text bg-danger text-white small px-1" style="font-size: 0.7rem;">M2</span>
                    </div>
                </div>
                <div style="flex: 1;">
                    <button type="submit" class="btn btn-primary w-70 fw-bold shadow-sm" style="height: 38px;">
                        <i class="bi bi-calculator-fill me-2"></i>HITUNG ANALISA
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($brickAnalysis))
    
    {{-- HASIL ANALISA --}}
    <div class="card shadow-sm border-0 position-relative">
        
        <div class="card-header bg-white pt-3 px-3 pb-0 border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" id="analysisTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 py-2 text-danger border-bottom-0" id="bata-tab" data-bs-toggle="tab" data-bs-target="#bata" type="button" role="tab" aria-controls="bata" aria-selected="true">
                        <i class="bi bi-bricks me-2"></i>Analisa Bata
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 py-2 text-secondary border-bottom-0" id="semen-tab" data-bs-toggle="tab" data-bs-target="#semen" type="button" role="tab" aria-controls="semen" aria-selected="false">
                        <i class="bi bi-box-seam me-2"></i>Analisa Semen
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 py-2 text-warning border-bottom-0" id="pasir-tab" data-bs-toggle="tab" data-bs-target="#pasir" type="button" role="tab" aria-controls="pasir" aria-selected="false">
                        <i class="bi bi-bucket-fill me-2"></i>Analisa Pasir
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 py-2 text-info border-bottom-0" id="air-tab" data-bs-toggle="tab" data-bs-target="#air" type="button" role="tab" aria-controls="air" aria-selected="false">
                        <i class="bi bi-droplet-fill me-2"></i>Kebutuhan Air
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="analysisTabsContent">
                
                {{-- TAB 1: BATA DENGAN FORM MULTI-SELECT --}}
                <div class="tab-pane fade show active" id="bata" role="tabpanel" aria-labelledby="bata-tab">
                    
                    {{-- UPDATE: Form Method GET ke Route Create --}}
                    <form action="{{ route('material-calculations.create') }}" method="GET" id="brickCompareForm">
                        
                        {{-- Hidden inputs tetap --}}
                        <input type="hidden" name="wall_length" value="{{ $inputs['wall_length'] }}">
                        <input type="hidden" name="wall_height" value="{{ $inputs['wall_height'] }}">
                        <input type="hidden" name="mortar_thickness" value="{{ $inputs['mortar_thickness'] }}">
                        <input type="hidden" name="formula_code" value="{{ $inputs['formula_code'] }}">
                        <input type="hidden" name="installation_type_id" value="{{ $inputs['installation_type_id'] }}">

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                                <thead class="bg-light text-secondary fw-bold border-top">
                                    <tr>
                                        <th class="py-3 ps-3 text-center" style="width: 40px;">
                                            <input type="checkbox" id="checkAllBricks" class="form-check-input">
                                        </th>
                                        <th class="py-3">Nama / Jenis / Merek</th>
                                        <th class="py-3">Dimensi</th>
                                        <th class="py-3">Toko & Alamat</th>
                                        <th class="py-3 text-end">Harga per Buah</th>
                                        <th class="py-3 text-center">Tebal Adukan</th>
                                        <th class="py-3 text-center">Luas Pasangan</th>
                                        <th class="py-3 text-center">Total Bata</th>
                                        <th class="py-3 text-end pe-4">Total Harga</th>
                                        <th class="py-3 ps-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($brickAnalysis as $item)
                                    <tr>
                                        <td class="ps-3 text-center">
                                            {{-- Use brick_ids[] for array --}}
                                            <input type="checkbox" name="brick_ids[]" value="{{ $item['id'] }}" class="form-check-input brick-checkbox">
                                        </td>
                                        <td class="fw-bold">
                                            {{ $item['material_name'] }} - {{ $item['type'] }} 
                                            <span class="text-muted fw-normal">({{ $item['brand'] }})</span>
                                        </td>
                                        <td>{{ $item['dimensions'] }}</td>
                                        <td>{{ $item['store'] }} <span class="text-muted small">({{ $item['address'] }})</span></td>
                                        <td class="text-end">Rp {{ number_format($item['price_per_piece'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $item['mortar_thickness'] }} cm</td>
                                        <td class="text-center">{{ $item['area_per_brick'] }}</td>
                                        <td class="text-center fw-bold">{{ number_format($item['total_qty_job'], 0) }} pcs</td>
                                        <td class="text-end fw-bold text-success pe-4">Rp {{ number_format($item['total_price_job'], 0, ',', '.') }}</td>
                                        <td class="ps-2">
                                            <a href="{{ route('material-calculations.create', [
                                                'brick_id' => $item['id'],
                                                'wall_length' => $inputs['wall_length'],
                                                'wall_height' => $inputs['wall_height'],
                                                'mortar_thickness' => $inputs['mortar_thickness'],
                                                'installation_type_id' => $inputs['installation_type_id'],
                                                'formula_code' => $inputs['formula_code']
                                            ]) }}" class="btn btn-primary rounded shadow" title="Hitung Detail">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- FLOATING ACTION BAR --}}
                        <div id="compareFloatingBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 p-3 bg-white shadow-lg rounded-pill border border-primary" style="display: none; z-index: 1050; min-width: 300px;">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <span class="fw-bold text-primary ms-2">
                                    <span id="selectedCount">0</span> Bata Dipilih
                                </span>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                                    <i class="bi bi-check2-circle me-2"></i>Lanjut ke Hitungan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- TAB LAIN TETAP SAMA --}}
                <div class="tab-pane fade" id="semen" role="tabpanel" aria-labelledby="semen-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                            <thead class="bg-light text-secondary fw-bold border-top">
                                <tr>
                                    <th class="py-3 ps-4">Nama / Jenis / Merek</th>
                                    <th class="py-3">Kemasan</th>
                                    <th class="py-3">Dimensi / Berat</th>
                                    <th class="py-3">Toko & Alamat</th>
                                    <th class="py-3 text-center bg-info bg-opacity-10 text-primary pe-4">
                                        Volume Adukan Dihasilkan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cementAnalysis as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['material_name'] }} <span class="text-muted fw-normal">({{ $item['brand'] }})</span></td>
                                    <td><span class="badge bg-secondary">{{ $item['packaging'] }}</span></td>
                                    <td>{{ $item['dimensions'] }}</td>
                                    <td>{{ $item['store'] }} <span class="text-muted small">({{ $item['address'] }})</span></td>
                                    <td class="text-center fw-bold text-primary bg-info bg-opacity-10 pe-4">
                                        {{ number_format($item['yield_mortar_per_unit'], 4) }} M3
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="pasir" role="tabpanel" aria-labelledby="pasir-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                            <thead class="bg-light text-secondary fw-bold border-top">
                                <tr>
                                    <th class="py-3 ps-4">Nama / Jenis / Merek</th>
                                    <th class="py-3">Kemasan</th>
                                    <th class="py-3">Dimensi / Berat</th>
                                    <th class="py-3">Toko & Alamat</th>
                                    <th class="py-3 text-center bg-info bg-opacity-10 text-primary pe-4">
                                        Volume Adukan Dihasilkan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sandAnalysis as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['material_name'] }} <span class="text-muted fw-normal">({{ $item['brand'] }})</span></td>
                                    <td><span class="badge bg-warning text-dark">{{ $item['packaging'] }}</span></td>
                                    <td>{{ $item['dimensions'] }}</td>
                                    <td>{{ $item['store'] }} <span class="text-muted small">({{ $item['address'] }})</span></td>
                                    <td class="text-center fw-bold text-primary bg-info bg-opacity-10 pe-4">
                                        {{ number_format($item['yield_mortar_per_unit'], 4) }} M3
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="air" role="tabpanel" aria-labelledby="air-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                            <thead class="bg-light text-secondary fw-bold">
                                <tr>
                                    <th class="py-3 ps-4">Referensi Bata</th>
                                    <th class="py-3 text-center">Tebal Adukan</th>
                                    <th class="py-3 text-center pe-4 bg-info bg-opacity-10">Kebutuhan Air</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($waterAnalysis as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['material_ref'] }}</td>
                                    <td class="text-center">{{ $item['mortar_thickness'] }} cm</td>
                                    <td class="text-center fw-bold text-primary pe-4 bg-info bg-opacity-10">
                                        {{ number_format($item['qty_per_m2'], 2) }} Liter / M2
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

@else
    <div class="text-center py-5">
        <div class="mb-3 text-muted opacity-25">
            <i class="bi bi-clipboard-data display-1"></i>
        </div>
        <h4 class="text-dark fw-bold">Siap Melakukan Analisa</h4>
        <p class="text-muted">Silakan pilih Formula, Tebal Adukan, dan Dimensi dinding.</p>
    </div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. AUTO CALCULATE AREA
        const pInput = document.getElementById('input_p');
        const tInput = document.getElementById('input_t');
        const areaOutput = document.getElementById('output_area');

        function calculateArea() {
            const p = parseFloat(pInput.value) || 0;
            const t = parseFloat(tInput.value) || 0;
            const area = p * t;
            areaOutput.value = area.toFixed(2);
        }

        if(pInput && tInput) {
            pInput.addEventListener('input', calculateArea);
            tInput.addEventListener('input', calculateArea);
        }

        // 2. BOOTSTRAP TABS
        var triggerTabList = [].slice.call(document.querySelectorAll('#analysisTabs button'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })

        // 3. CHECKBOX & FLOATING ACTION BAR LOGIC
        const checkAll = document.getElementById('checkAllBricks');
        const brickCheckboxes = document.querySelectorAll('.brick-checkbox');
        const floatingBar = document.getElementById('compareFloatingBar');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateFloatingBar() {
            const checkedCount = document.querySelectorAll('.brick-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;
            
            if (checkedCount > 0) {
                floatingBar.style.display = 'block';
            } else {
                floatingBar.style.display = 'none';
            }
        }

        if(checkAll) {
            checkAll.addEventListener('change', function() {
                brickCheckboxes.forEach(cb => cb.checked = this.checked);
                updateFloatingBar();
            });
        }

        brickCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateFloatingBar);
        });
    });
</script>
@endpush