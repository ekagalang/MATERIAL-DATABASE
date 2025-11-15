<div class="card">
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

    <form action="{{ route('cats.update', $cat->id) }}" method="POST" enctype="multipart/form-data" id="catForm">
        @csrf
        @method('PUT')

        <div style="display: flex; gap: 40px;">
            <!-- Kolom kiri: fields -->
            <div style="flex: 0 0 calc(65% - 20px); max-width: calc(65% - 20px);">

                {{-- Nama cat disembunyikan, akan dikirim apa adanya/otomatis --}}
                <input type="hidden" name="cat_name" id="cat_name" value="{{ old('cat_name', $cat->cat_name) }}">

                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="type" id="type" value="{{ old('type', $cat->type) }}" class="autocomplete-input" data-field="type" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="brand" id="brand" value="{{ old('brand', $cat->brand) }}" class="autocomplete-input" data-field="brand" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Sub Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="sub_brand" id="sub_brand" value="{{ old('sub_brand', $cat->sub_brand) }}" class="autocomplete-input" data-field="sub_brand" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Warna</label>
                    <div style="flex: 1; position: relative; display: flex; gap: 8px;">
                        <div style="position: relative; flex: 1;">
                            <input type="text" name="color_name" id="color_name" value="{{ old('color_name', $cat->color_name) }}" class="autocomplete-input" data-field="color_name" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="color_name-list"></div>
                        </div>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="color_code" id="color_code" value="{{ old('color_code', $cat->color_code) }}" class="autocomplete-input" data-field="color_code" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="color_code-list"></div>
                        </div>
                    </div>
                </div>

                {{-- Bentuk tidak ditampilkan agar selaras dengan Create --}}

                <div class="row">
                    <label>Volume Isi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <input type="number" name="volume" id="volume" value="{{ old('volume', $cat->volume) }}" step="0.01" min="0" placeholder="0.00" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="volume_unit" id="volume_unit" value="{{ old('volume_unit', $cat->volume_unit) }}" class="autocomplete-input" data-field="volume_unit" placeholder="L, ml, dll" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="volume_unit-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Kemasan -->
                <div class="row">
                    <label>Kemasan</label>
                    <div style="display: flex; flex: 1; gap: 6px; align-items: center;">
                        <select name="package_unit" id="package_unit" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <option value="">-- Satuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}" data-weight="{{ $unit->package_weight }}" {{ old('package_unit', $cat->package_unit) == $unit->code ? 'selected' : '' }}>
                                    {{ $unit->code }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="package_weight_gross" id="package_weight_gross" value="{{ old('package_weight_gross', $cat->package_weight_gross) }}" step="0.01" min="0" placeholder="Berat Kotor" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="white-space: nowrap; font-size: 13px;">Kg</span>
                        <input type="number" name="package_weight_net" id="package_weight_net" value="{{ old('package_weight_net', $cat->package_weight_net) }}" step="0.01" min="0" placeholder="Berat Bersih" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="white-space: nowrap; font-size: 13px;">Kg</span>
                    </div>
                </div>
                <div style="margin-left: 140px; margin-bottom: 15px;">
                    <small style="color: #7f8c8d;">Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight:bold;color:#27ae60;">-</span></small>
                </div>

                <div class="row">
                    <label>Harga</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $cat->purchase_price) }}">
                        <input type="text" id="purchase_price_display" value="{{ old('purchase_price', $cat->purchase_price) }}" inputmode="numeric" placeholder="0" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="padding: 0 4px;">/</span>
                        <div style="position: relative; flex: 0 0 50%;">
                            <input type="text" name="price_unit" id="price_unit" value="{{ old('price_unit', $cat->price_unit) }}" class="autocomplete-input" data-field="price_unit" placeholder="Pcs, Kg, dll" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                            <div class="autocomplete-list" id="price_unit-list"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Harga Komparasi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="comparison_price_per_kg" id="comparison_price_per_kg" value="{{ old('comparison_price_per_kg', $cat->comparison_price_per_kg) }}">
                        <input type="text" id="comparison_price_display" inputmode="numeric" placeholder="0" value="{{ $cat->comparison_price_per_kg ? number_format($cat->comparison_price_per_kg, 0, ',', '.') : '' }}" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="padding: 0 4px;">/</span>
                        <input type="text" value="Kg" readonly style="flex: 0 0 50%; background: #f0f0f0; padding: 7px; border: 1px solid #ddd; border-radius: 2px;">
                    </div>
                </div>

                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="store" id="store" value="{{ old('store', $cat->store) }}" class="autocomplete-input" data-field="store" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Singkat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="short_address" id="short_address" value="{{ old('short_address', $cat->short_address) }}" class="autocomplete-input" data-field="short_address" autocomplete="off" placeholder="Contoh: Roxy, CitraLand, dsb" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="short_address-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Lengkap</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="address" id="address" value="{{ old('address', $cat->address) }}" class="autocomplete-input" data-field="address" placeholder="Alamat lengkap toko" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: foto -->
            <div style="flex: 0 0 calc(35% - 20px); max-width: calc(35% - 20px);">
                <div class="right" id="photoPreviewArea" style="border: 1px solid #999; height: 380px; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; cursor: pointer; position: relative; overflow: hidden; width: 100%;">
                    @if($cat->photo_url)
                        <div id="photoPlaceholder" style="display:none;text-align: center; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                            <div>Klik untuk upload foto</div>
                            <div style="font-size: 12px; margin-top: 5px;">JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        <img id="photoPreview" src="{{ $cat->photo_url }}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
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
                    <span style="cursor: pointer; {{ $cat->photo_url ? '' : 'display:none;' }}" id="deletePhotoBtn">✕ Hapus</span>
                </div>
            </div>
        </div>

        <div class="btnArea" style="text-align: right; margin-top: 25px;">
            <button type="button" class="btn red" onclick="window.parent.document.getElementById('closeModal').click()" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c;">Batalkan</button>
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
    .autocomplete-list {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 200px;
        overflow-y: auto;
        z-index: 10000;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: none;
        margin-top: 1px;
    }
    .autocomplete-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s ease;
    }
    .autocomplete-item:hover {
        background: #f5f5f5;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    /* Scrollbar untuk autocomplete */
    .autocomplete-list::-webkit-scrollbar {
        width: 6px;
    }
    .autocomplete-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .autocomplete-list::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .autocomplete-list::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script src="/js/cat-form.js"></script>
<script>
    if (typeof initCatForm === 'function') {
        initCatForm();
    }
</script>
