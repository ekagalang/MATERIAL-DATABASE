@extends('layouts.app')

@section('title', 'Tambah Material')

@section('content')
<div class="card">
    <h2>Tambah Material CAT</h2>

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

    <form action="{{ route('materials.store') }}" method="POST" enctype="multipart/form-data" id="materialForm">
        @csrf

        <div style="display: flex; gap: 40px;">
            <!-- Form Fields - Kolom Kiri -->
            <div style="flex: 0 0 calc(65% - 20px); max-width: calc(65% - 20px);">

                {{-- Nama Material disembunyikan, akan diisi otomatis --}}
                <input type="hidden" name="material_name" id="material_name" value="{{ old('material_name') }}">

                <!-- Jenis -->
                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="type" id="type" class="autosuggest-field autocomplete-input" data-field="type" list="type-list" placeholder="Ketik atau pilih..." style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="type-list"></datalist>
                        <div class="autocomplete-list" id="type-suggest"></div>
                    </div>
                </div>

                <!-- Merek -->
                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="brand" id="brand" class="autosuggest-field autocomplete-input" data-field="brand" list="brand-list" placeholder="Ketik atau pilih..." style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="brand-list"></datalist>
                        <div class="autocomplete-list" id="brand-suggest"></div>
                    </div>
                </div>

                <!-- Sub Merek -->
                <div class="row">
                    <label>Sub Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="sub_brand" id="sub_brand" class="autosuggest-field autocomplete-input" data-field="sub_brand" list="sub_brand-list" placeholder="Ketik atau pilih..." style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="sub_brand-list"></datalist>
                        <div class="autocomplete-list" id="sub_brand-suggest"></div>
                    </div>
                </div>

                <!-- Warna -->
                <div class="row">
                    <label>Warna</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <div style="position: relative; flex: 1;">
                            <input type="text" name="color_name" id="color_name" class="autosuggest-field autocomplete-input" data-field="color_name" list="color_name-list" placeholder="Nama warna" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <datalist id="color_name-list"></datalist>
                            <div class="autocomplete-list" id="color_name-suggest"></div>
                        </div>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="color_code" id="color_code" class="autosuggest-field autocomplete-input" data-field="color_code" list="color_code-list" placeholder="Kode" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <datalist id="color_code-list"></datalist>
                            <div class="autocomplete-list" id="color_code-suggest"></div>
                        </div>
                    </div>
                </div>

                {{-- Bentuk tidak ditampilkan sesuai template --}}

                <!-- Volume Isi -->
                <div class="row">
                    <label>Volume Isi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <input type="number" name="volume" id="volume" value="{{ old('volume') }}" step="0.01" min="0" placeholder="0.00" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="volume_unit" id="volume_unit" class="autosuggest-field autocomplete-input" data-field="volume_unit" list="volume_unit-list" placeholder="Satuan (L, ml, dll)" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <datalist id="volume_unit-list"></datalist>
                            <div class="autocomplete-list" id="volume_unit-suggest"></div>
                        </div>
                    </div>
                </div>

                <!-- Kemasan -->
                <div class="row">
                    <label>Kemasan</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <select name="package_unit" id="package_unit" style="flex: 0 0 30%; padding: 7px; border: 1px solid #999; border-radius: 2px; width: 100%;">
                            <option value="">-- Satuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}" data-weight="{{ $unit->package_weight }}" {{ old('package_unit') == $unit->code ? 'selected' : '' }}>
                                    {{ $unit->code }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="package_weight_gross" id="package_weight_gross" value="{{ old('package_weight_gross') }}" step="0.01" min="0" placeholder="Berat Kotor (Kg)" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">Kg
                        <input type="number" name="package_weight_net" id="package_weight_net" value="{{ old('package_weight_net') }}" step="0.01" min="0" placeholder="Berat Bersih (Kg)" style="flex: 0 0 35%; padding: 7px; border: 1px solid #999; border-radius: 2px;">Kg
                    </div>
                </div>
                <div style="margin-left: 140px; margin-bottom: 15px;">
                    <small style="color: #7f8c8d;">
                        Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight: bold; color: #27ae60;">-</span>
                    </small>
                </div>

                <!-- Harga -->
                <div class="row">
                    <label>Harga</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price') }}">
                        <input type="text" id="purchase_price_display" value="{{ old('purchase_price') }}" inputmode="numeric" placeholder="0" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="padding: 0 4px;">/</span>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="price_unit" id="price_unit" class="autosuggest-field autocomplete-input" data-field="price_unit" list="price_unit-list" placeholder="Satuan (Pcs, Kg, dll)" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <datalist id="price_unit-list"></datalist>
                            <div class="autocomplete-list" id="price_unit-suggest"></div>
                        </div>
                    </div>
                </div>

                <!-- Harga Komparasi -->
                <div class="row">
                    <label>Harga Komparasi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="text" id="comparison_price_display" readonly style="flex: 1; background: #f0f0f0; color: #666; padding: 7px; border: 1px solid #ddd; border-radius: 2px;" placeholder="0">
                        <span style="padding: 0 4px;">/</span>
                        <input type="text" value="Kg" readonly style="flex: 0 0 50%; background: #f0f0f0; padding: 7px; border: 1px solid #ddd; border-radius: 2px;">
                    </div>
                </div>

                <!-- Toko -->
                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="store" id="store" class="autosuggest-field autocomplete-input" data-field="store" list="store-list" placeholder="Ketik atau pilih..." style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="store-list"></datalist>
                        <div class="autocomplete-list" id="store-suggest"></div>
                    </div>
                </div>

                <!-- Alamat Singkat -->
                <div class="row">
                    <label>Alamat Singkat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="short_address" id="short_address" value="{{ old('short_address') }}" class="autosuggest-field autocomplete-input" data-field="short_address" list="short_address-list" placeholder="Contoh: Roxy, CitraLand, dsb" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="short_address-list"></datalist>
                        <div class="autocomplete-list" id="short_address-suggest"></div>
                    </div>
                </div>

                <!-- Alamat Lengkap -->
                <div class="row">
                    <label>Alamat Lengkap</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="address" id="address" class="autosuggest-field autocomplete-input" data-field="address" list="address-list" value="{{ old('address') }}" placeholder="Ketik atau pilih..." style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <datalist id="address-list"></datalist>
                        <div class="autocomplete-list" id="address-suggest"></div>
                    </div>
                </div>

            </div>

            <!-- Photo Upload Area - Kolom Kanan -->
            <div style="flex: 0 0 calc(35% - 20px); max-width: calc(35% - 20px);">
                <div class="right" id="photoPreviewArea" style="border: 1px solid #999; height: 380px; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; cursor: pointer; position: relative; overflow: hidden; width: 100%;">
                    <div id="photoPlaceholder" style="text-align: center; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                        <div>Klik untuk upload foto</div>
                        <div style="font-size: 12px; margin-top: 5px;">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <img id="photoPreview" src="" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                </div>
                <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">
                <div class="uploadDel" style="margin-top: 10px; font-size: 12px; color: #c02c2c;">
                    <span style="margin-right: 20px; cursor: pointer;" id="uploadBtn">↑ Upload</span>
                    <span style="cursor: pointer; display: none;" id="deletePhotoBtn">✕ Hapus</span>
                </div>
            </div>

        </div>

        <!-- Buttons -->
        <div class="btnArea" style="text-align: right; margin-top: 25px;">
            <a href="{{ route('materials.index') }}" class="btn red" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c; text-decoration: none; display: inline-block;">Batalkan</a>
            <button type="submit" class="btn green" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: #76b245; color: #fff;">Simpan</button>
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
    // Auto-suggest (datalist + dropdown kustom)
    const autosuggestInputs = document.querySelectorAll('.autosuggest-field');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        const datalistId = input.getAttribute('list');
        const datalist = datalistId ? document.getElementById(datalistId) : null;
        const suggestList = document.getElementById(`${field}-suggest`);
        let debounceTimer;

        function populate(values) {
            if (datalist) {
                while (datalist.firstChild) datalist.removeChild(datalist.firstChild);
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    datalist.appendChild(opt);
                });
            }
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = v;
                    item.addEventListener('click', function() {
                        input.value = v;
                        suggestList.style.display = 'none';
                    });
                    suggestList.appendChild(item);
                });
                suggestList.style.display = values.length > 0 ? 'block' : 'none';
            }
        }

        function loadSuggestions(term = '') {
            const url = `/api/materials/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
            fetch(url)
                .then(resp => resp.json())
                .then(populate)
                .catch(() => {});
        }

        input.addEventListener('focus', () => loadSuggestions(''));
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const term = this.value || '';
            debounceTimer = setTimeout(() => loadSuggestions(term), 200);
        });

        document.addEventListener('click', function(e) {
            if (suggestList && e.target !== input && !suggestList.contains(e.target)) {
                suggestList.style.display = 'none';
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

    // Susun otomatis material_name (hidden) dari field utama
    const fType = document.getElementById('type');
    const fBrand = document.getElementById('brand');
    const fSubBrand = document.getElementById('sub_brand');
    const fColor = document.getElementById('color_name');
    const fVol = document.getElementById('volume');
    const fVolUnit = document.getElementById('volume_unit');
    const fMatName = document.getElementById('material_name');
    function composeName() {
        const parts = [];
        if (fType && fType.value) parts.push(fType.value);
        if (fBrand && fBrand.value) parts.push(fBrand.value);
        if (fSubBrand && fSubBrand.value) parts.push(fSubBrand.value);
        if (fColor && fColor.value) parts.push(fColor.value);
        const volPart = (fVol && fVol.value ? fVol.value : '') + (fVolUnit && fVolUnit.value ? fVolUnit.value : '');
        if (volPart.trim()) parts.push(volPart.trim());
        if (fMatName) fMatName.value = parts.join(' ').replace(/\s+/g,' ').trim();
    }
    [fType, fBrand, fSubBrand, fColor, fVol, fVolUnit].forEach(el => el && el.addEventListener('input', composeName));
    composeName();

    // Kalkulasi: Berat Kemasan (kalkulasi) + Harga Komparasi per Kg
    const grossInput = document.getElementById('package_weight_gross');
    const netInput = document.getElementById('package_weight_net');
    const unitSelect = document.getElementById('package_unit');
    const netCalcDisplay = document.getElementById('net_weight_display');
    const purchasePrice = document.getElementById('purchase_price');
    const purchasePriceDisplay = document.getElementById('purchase_price_display');
    const comparisonPriceDisplay = document.getElementById('comparison_price_display');

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
        comparisonPriceDisplay.value = price > 0 && net > 0 ? Math.round(price / net).toLocaleString('id-ID') : '0';
    }
    [grossInput, netInput].forEach(el => el && el.addEventListener('input', () => { updateNetCalc(); updateComparison(); }));
    unitSelect?.addEventListener('change', () => { updateNetCalc(); updateComparison(); });
    purchasePrice?.addEventListener('input', updateComparison);
    // Format Rupiah saat input harga (tampilan) + sinkron ke hidden
    function unformatRupiah(str) { return (str || '').toString().replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.]/g,''); }
    function formatRupiah(num) { const n = Number(num||0); return isNaN(n) ? '' : n.toLocaleString('id-ID'); }
    function syncPriceFromDisplay() {
        const raw = unformatRupiah(purchasePriceDisplay?.value || '');
        purchasePrice.value = raw || '';
        // Tampilkan pemisah ribuan
        if (purchasePriceDisplay) purchasePriceDisplay.value = raw ? formatRupiah(raw) : '';
        updateComparison();
    }
    purchasePriceDisplay?.addEventListener('input', syncPriceFromDisplay);
    // Inisialisasi tampilan harga
    if (purchasePriceDisplay && purchasePrice && purchasePrice.value) {
        purchasePriceDisplay.value = formatRupiah(purchasePrice.value);
    }
    updateNetCalc();
    updateComparison();
});
</script>
@endsection
