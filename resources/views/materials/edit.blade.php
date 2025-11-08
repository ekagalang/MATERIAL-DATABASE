@extends('layouts.app')

@section('title', 'Edit Material')

@section('content')
<div class="card">
    <h2>Edit Material</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin: 10px 0 0 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('materials.update', $material->id) }}" method="POST" enctype="multipart/form-data" id="materialForm">
        @csrf
        @method('PUT')

        <div style="display: flex; gap: 40px;">
            <!-- Kolom kiri: fields -->
            <div style="flex: 0 0 calc(65% - 20px); max-width: calc(65% - 20px);">

                {{-- Nama Material disembunyikan, akan dikirim apa adanya/otomatis --}}
                <input type="hidden" name="material_name" id="material_name" value="{{ old('material_name', $material->material_name) }}">

                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="type" id="type" value="{{ old('type', $material->type) }}" class="autocomplete-input" data-field="type" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="brand" id="brand" value="{{ old('brand', $material->brand) }}" class="autocomplete-input" data-field="brand" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Sub Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="sub_brand" id="sub_brand" value="{{ old('sub_brand', $material->sub_brand) }}" class="autocomplete-input" data-field="sub_brand" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Warna</label>
                    <div style="flex: 1; position: relative; display: flex; gap: 8px;">
                        <div style="position: relative; flex: 1;">
                            <input type="text" name="color_name" id="color_name" value="{{ old('color_name', $material->color_name) }}" class="autocomplete-input" data-field="color_name" autocomplete="off" style="width: 100%;">
                            <div class="autocomplete-list" id="color_name-list"></div>
                        </div>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="color_code" id="color_code" value="{{ old('color_code', $material->color_code) }}" class="autocomplete-input" data-field="color_code" autocomplete="off" style="width: 100%;">
                            <div class="autocomplete-list" id="color_code-list"></div>
                        </div>
                    </div>
                </div>

                {{-- Bentuk tidak ditampilkan agar selaras dengan Create --}}

                <div class="row">
                    <label>Volume Isi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <input type="number" name="volume" id="volume" value="{{ old('volume', $material->volume) }}" step="0.01" min="0" placeholder="0.00" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="volume_unit" id="volume_unit" value="{{ old('volume_unit', $material->volume_unit) }}" class="autocomplete-input" data-field="volume_unit" placeholder="L, ml, dll" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="volume_unit-list"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Kemasan</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <input type="number" name="package_weight_gross" id="package_weight_gross" value="{{ old('package_weight_gross', $material->package_weight_gross) }}" step="0.01" min="0" placeholder="Berat Kotor (Kg)" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <input type="number" name="package_weight_net" id="package_weight_net" value="{{ old('package_weight_net', $material->package_weight_net) }}" step="0.01" min="0" placeholder="Berat Bersih (Kg)" style="flex: 0 0 35%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <select name="package_unit" id="package_unit" style="flex: 0 0 30%; padding: 7px; border: 1px solid #999; border-radius: 2px; width: 100%;">
                            <option value="">-- Satuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}" data-weight="{{ $unit->package_weight }}" {{ old('package_unit', $material->package_unit) == $unit->code ? 'selected' : '' }}>
                                    {{ $unit->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-left: 140px; margin-bottom: 15px;">
                    <small style="color: #7f8c8d;">Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight:bold;color:#27ae60;">-</span></small>
                </div>

                <div class="row">
                    <label>Harga</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $material->purchase_price) }}">
                        <input type="text" id="purchase_price_display" value="{{ old('purchase_price', $material->purchase_price) }}" inputmode="numeric" placeholder="0" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="padding: 0 4px;">/</span>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="price_unit" id="price_unit" value="{{ old('price_unit', $material->price_unit) }}" class="autocomplete-input" data-field="price_unit" placeholder="Pcs, Kg, dll" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="price_unit-list"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Harga Komparasi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="text" id="comparison_price_display" readonly style="flex: 1; background: #f0f0f0; color: #666; padding: 7px; border: 1px solid #ddd; border-radius: 2px;" placeholder="0" value="{{ $material->comparison_price_per_kg ? number_format($material->comparison_price_per_kg, 0, ',', '.') : '' }}">
                        <span style="padding: 0 4px;">/</span>
                        <input type="text" value="Kg" readonly style="flex: 0 0 50%; background: #f0f0f0; padding: 7px; border: 1px solid #ddd; border-radius: 2px;">
                    </div>
                </div>

                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="store" id="store" value="{{ old('store', $material->store) }}" class="autocomplete-input" data-field="store" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Singkat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="short_address" id="short_address" value="{{ old('short_address', $material->short_address) }}" class="autocomplete-input" data-field="short_address" autocomplete="off" placeholder="Contoh: Roxy, CitraLand, dsb" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="short_address-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Lengkap</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="address" id="address" value="{{ old('address', $material->address) }}" class="autocomplete-input" data-field="address" placeholder="Alamat lengkap toko" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: foto -->
            <div style="flex: 0 0 calc(35% - 20px); max-width: calc(35% - 20px);">
                <div class="right" id="photoPreviewArea" style="border: 1px solid #999; height: 380px; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; cursor: pointer; position: relative; overflow: hidden; width: 100%;">
                    @if($material->photo_url)
                        <div id="photoPlaceholder" style="display:none;text-align: center; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                            <div>Klik untuk upload foto</div>
                            <div style="font-size: 12px; margin-top: 5px;">JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        <img id="photoPreview" src="{{ $material->photo_url }}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div id="photoPlaceholder" style="text-align: center; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                            <div>Klik untuk upload foto</div>
                            <div style="font-size: 12px; margin-top: 5px;">JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        <img id="photoPreview" src="" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    @endif
                </div>
                <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">
                <div class="uploadDel" style="margin-top: 10px; font-size: 12px; color: #c02c2c;">
                    <span style="margin-right: 20px; cursor: pointer;" id="uploadBtn">↑ Upload</span>
                    <span style="cursor: pointer; {{ $material->photo_url ? '' : 'display:none;' }}" id="deletePhotoBtn">✕ Hapus</span>
                </div>
            </div>
        </div>

        <div class="btnArea" style="text-align: right; margin-top: 25px;">
            <a href="{{ route('materials.index') }}" class="btn red" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c; text-decoration: none; display: inline-block;">Batalkan</a>
            <button type="submit" class="btn green" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: #76b245; color: #fff;">Update</button>
        </div>
    </form>
</div>

<style>
    .row { display: flex; margin-bottom: 15px; align-items: center; }
    label { width: 140px; padding-top: 4px; font-size: 14px; font-weight: 600; }
    input, select { flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px; }
    .mini { width: 120px !important; flex: initial !important; }
    .kg { padding-left: 5px; margin-top: 6px; font-size: 14px; }
    .autocomplete-list { position: absolute; background: #fff; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; z-index: 1000; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: none; }
    .autocomplete-item { padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
    .autocomplete-item:hover { background: #f5f5f5; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-suggest: tampilkan daftar saat fokus dan saat mengetik
    const autocompleteInputs = document.querySelectorAll('.autocomplete-input');
    
    autocompleteInputs.forEach(input => {
        const field = input.dataset.field;
        const listElement = document.getElementById(`${field}-list`);
        let debounceTimer;

        function renderList(data) {
            listElement.innerHTML = '';
            if (data.length > 0) {
                data.forEach(value => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = value;
                    item.addEventListener('click', function() {
                        input.value = value;
                        listElement.style.display = 'none';
                    });
                    listElement.appendChild(item);
                });
                listElement.style.display = 'block';
            } else {
                listElement.style.display = 'none';
            }
        }

        function loadSuggestions(term = '') {
            fetch(`/api/materials/field-values/${field}?search=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(renderList)
                .catch(() => {});
        }

        input.addEventListener('focus', function() { loadSuggestions(''); });
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const term = this.value || '';
            debounceTimer = setTimeout(() => loadSuggestions(term), 200);
        });

        document.addEventListener('click', function(e) {
            if (e.target !== input && !listElement.contains(e.target)) {
                listElement.style.display = 'none';
            }
        });
    });

    // Photo upload functionality
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoPreviewArea = document.getElementById('photoPreviewArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const deletePhotoBtn = document.getElementById('deletePhotoBtn');

    photoPreviewArea.addEventListener('click', function() { photoInput.click(); });
    uploadBtn.addEventListener('click', function(e) { e.preventDefault(); photoInput.click(); });
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                photoPlaceholder.style.display = 'none';
                deletePhotoBtn.style.display = 'inline';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    deletePhotoBtn.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        photoInput.value = '';
        photoPreview.src = '';
        photoPreview.style.display = 'none';
        photoPlaceholder.style.display = 'block';
        deletePhotoBtn.style.display = 'none';
    });

    // Kalkulasi: Berat Kemasan (kalkulasi) + Harga Komparasi per Kg
    const grossInput = document.getElementById('package_weight_gross');
    const netInput = document.getElementById('package_weight_net');
    const unitSelect = document.getElementById('package_unit');
    const netCalcDisplay = document.getElementById('net_weight_display');
    const purchasePrice = document.getElementById('purchase_price');
    const purchasePriceDisplay = document.getElementById('purchase_price_display');
    const comparisonPriceDisplay = document.getElementById('comparison_price_display');
    const priceUnitInput = document.getElementById('price_unit');

    function updateNetCalc() {
        const gross = parseFloat(grossInput?.value) || 0;
        const tare = parseFloat(unitSelect?.selectedOptions[0]?.dataset?.weight) || 0;
        const netCalc = Math.max(gross - tare, 0);
        if (netCalcDisplay) netCalcDisplay.textContent = netCalc > 0 ? netCalc.toFixed(2) + ' Kg' : '-';
        return netCalc;
    }
    function updateComparison() {
        const price = parseFloat(purchasePrice?.value) || 0;
        const netManual = parseFloat(netInput?.value) || 0;
        const netCalc = updateNetCalc();
        const net = netManual > 0 ? netManual : netCalc;
        comparisonPriceDisplay.value = price > 0 && net > 0 ? 'Rp ' + Math.round(price / net).toLocaleString('id-ID') + ' / Kg' : '';
    }
    [grossInput, netInput].forEach(el => el && el.addEventListener('input', () => { updateNetCalc(); updateComparison(); }));
    // Sinkronkan satuan harga mengikuti satuan kemasan (otomatis, namun bisa diubah manual)
    let priceUnitDirty = false;
    priceUnitInput?.addEventListener('input', () => { priceUnitDirty = true; });
    function syncPriceUnit() {
        const unit = unitSelect?.value || '';
        if (!priceUnitInput) return;
        if (!priceUnitDirty || !priceUnitInput.value) {
            if (unit) priceUnitInput.value = unit;
        }
    }
    unitSelect?.addEventListener('change', () => { updateNetCalc(); updateComparison(); syncPriceUnit(); });
    purchasePrice?.addEventListener('input', updateComparison);
    // Format Rupiah saat input harga (tampilan) + sinkron ke hidden
    function unformatRupiah(str) { return (str || '').toString().replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.]/g,''); }
    function formatRupiah(num) { const n = Number(num||0); return isNaN(n) ? '' : n.toLocaleString('id-ID'); }
    function syncPriceFromDisplay() {
        const raw = unformatRupiah(purchasePriceDisplay?.value || '');
        purchasePrice.value = raw || '';
        if (purchasePriceDisplay) purchasePriceDisplay.value = raw ? formatRupiah(raw) : '';
        updateComparison();
    }
    purchasePriceDisplay?.addEventListener('input', syncPriceFromDisplay);
    if (purchasePriceDisplay && purchasePrice && purchasePrice.value) {
        purchasePriceDisplay.value = formatRupiah(purchasePrice.value);
    }
    updateNetCalc();
    updateComparison();
    syncPriceUnit();
});
</script>
@endsection
