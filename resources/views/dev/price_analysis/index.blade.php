@extends('layouts.app')

@section('title', 'Analisa Harga Material')

@section('content')
{{-- HEADER --}}
<div class="mb-4 text-center">
    <h2>📊 Analisa Efisiensi Material</h2>
    <p class="text-muted small">
        Komparasi yield (hasil guna) material berdasarkan input pekerjaan
    </p>
</div>

{{-- CONFIGURATION CARD --}}
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-4">
        
        <form action="{{ route('dev.price-analysis.calculate') }}" method="POST">
            @csrf
            
            {{-- ROW FLEX CUSTOM --}}
            <div class="d-md-flex align-items-end mb-3">
                
                {{-- 1. ITEM PEKERJAAN (60%) --}}
                <div style="flex: 0 0 60%; max-width: 60%;" class="pe-2">
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

                {{-- 2. TEBAL ADUKAN (10%) --}}
                <div style="flex: 0 0 10%; max-width: 10%;" class="pe-2">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-center" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">TEBAL</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="mortar_thickness" class="form-control fw-bold text-center px-1" value="{{ $inputs['mortar_thickness'] ?? 2.0 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">cm</span>
                    </div>
                </div>

                {{-- 3. PANJANG (10%) --}}
                <div style="flex: 0 0 10%; max-width: 10%;" class="pe-2">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-center" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">PANJANG</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" id="input_p" name="wall_length" class="form-control fw-bold text-center px-1" placeholder="0" value="{{ $inputs['wall_length'] ?? 1 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">m</span>
                    </div>
                </div>

                {{-- 4. TINGGI (10%) --}}
                <div style="flex: 0 0 10%; max-width: 10%;" class="pe-2">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-center" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border">TINGGI</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.01" id="input_t" name="wall_height" class="form-control fw-bold text-center px-1" placeholder="0" value="{{ $inputs['wall_height'] ?? 1 }}">
                        <span class="input-group-text bg-light text-muted small px-1" style="font-size: 0.7rem;">m</span>
                    </div>
                </div>

                {{-- 5. LUAS (10%) --}}
                <div style="flex: 0 0 10%; max-width: 10%;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-center" style="font-size: 0.75rem;">
                        <span class="badge bg-primary text-white border border-primary">LUAS</span>
                    </label>
                    <div class="input-group">
                        <input type="text" id="output_area" class="form-control fw-bold text-center bg-light text-primary px-1" readonly value="{{ isset($inputs['wall_area']) ? number_format($inputs['wall_area'], 2) : '1.00' }}">
                        <span class="input-group-text bg-primary text-white small px-1" style="font-size: 0.7rem;">m²</span>
                    </div>
                </div>

            </div>

            {{-- FOOTER INFO --}}
            <div class="row mt-4 align-items-center">
                <div class="col-md-8">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> 
                        Otomatis menggunakan rasio adukan standar: <strong>{{ $inputs['mortar_name'] ?? '1 Semen : 3 Pasir' }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                        <i class="bi bi-calculator-fill me-2"></i>HITUNG ANALISA
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

@if(isset($brickAnalysis))
    
    {{-- INFO HASIL --}}
    <div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm py-2" role="alert" style="background: #e0f2fe; color: #0369a1;">
        <i class="bi bi-clipboard-check-fill fs-4 me-3"></i>
        <div class="small">
            <strong>Hasil Perhitungan:</strong> Formula <u>{{ $inputs['formula_name'] }}</u> 
            dengan Tebal Adukan <u>{{ $inputs['mortar_thickness'] }} cm</u> 
            pada bidang seluas <u>{{ number_format($inputs['wall_area'], 2) }} m²</u>.
        </div>
    </div>

    {{-- HASIL ANALISA --}}
    <div class="card shadow-sm border-0">
        
        {{-- TABS --}}
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

        {{-- CONTENT --}}
        <div class="card-body p-0">
            <div class="tab-content" id="analysisTabsContent">
                
                {{-- TAB 1: BATA --}}
                <div class="tab-pane fade show active" id="bata" role="tabpanel" aria-labelledby="bata-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                            <thead class="bg-light text-secondary fw-bold border-top">
                                <tr>
                                    <th class="py-3 ps-4">Nama / Jenis / Merek</th>
                                    <th class="py-3">Dimensi</th>
                                    <th class="py-3">Toko & Alamat</th>
                                    <th class="py-3 text-end">Harga Satuan</th>
                                    <th class="py-3 text-center">Tebal Adukan</th>
                                    <th class="py-3 text-center">Luas Pasangan</th>
                                    <th class="py-3 text-center">Total Bata / Job</th>
                                    <th class="py-3 text-end pe-4">Total Harga / Job</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brickAnalysis as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: SEMEN --}}
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
                                        Volume Adukan Dihasilkan<br><small>(Per Sak)</small>
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
                                        {{ number_format($item['yield_mortar_per_unit'], 4) }} m³
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 3: PASIR --}}
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
                                        Volume Adukan Dihasilkan<br><small>(Per m³ Pasir)</small>
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
                                        {{ number_format($item['yield_mortar_per_unit'], 4) }} m³
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB 4: AIR --}}
                <div class="tab-pane fade" id="air" role="tabpanel" aria-labelledby="air-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                            <thead class="bg-light text-secondary fw-bold">
                                <tr>
                                    <th class="py-3 ps-4">Referensi Bata</th>
                                    <th class="py-3 text-center">Tebal Adukan</th>
                                    <th class="py-3 text-center pe-4 bg-info bg-opacity-10">Kebutuhan Air <br><small>(Liter / m²)</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($waterAnalysis as $item)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $item['material_ref'] }}</td>
                                    <td class="text-center">{{ $item['mortar_thickness'] }} cm</td>
                                    <td class="text-center fw-bold text-primary pe-4 bg-info bg-opacity-10">
                                        {{ number_format($item['qty_per_m2'], 2) }} Liter/m²
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
    {{-- EMPTY STATE --}}
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
    });
</script>
@endpush