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

    <form action="{{ route('sands.update', $sand->id) }}" method="POST" enctype="multipart/form-data" id="sandForm">
        @csrf
        @method('PUT')

        <div style="display: flex; gap: 40px;">
            <!-- Kolom kiri: fields -->
            <div style="flex: 0 0 calc(65% - 20px); max-width: calc(65% - 20px);">

                {{-- Nama pasir disembunyikan, akan dikirim apa adanya/otomatis --}}
                <input type="hidden" name="sand_name" id="sand_name" value="{{ old('sand_name', $sand->sand_name) }}">

                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="type" id="type" value="{{ old('type', $sand->type) }}" class="autocomplete-input" data-field="type" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="brand" id="brand" value="{{ old('brand', $sand->brand) }}" class="autocomplete-input" data-field="brand" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <!-- Dimensi Kemasan (P × L × T) -->
                <div class="row">
                    <label>Dimensi Kemasan</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <!-- Panjang -->
                            <input type="text" id="dimension_length_input" value="{{ old('dimension_length', $sand->dimension_length) }}" placeholder="P" style="padding: 7px 8px; border: 1px solid #999; border-radius: 2px; font-size: 13px; width: 100%;">
                            <select id="dimension_length_unit" style="padding: 7px 4px; border: 1px solid #999; border-radius: 2px; font-size: 12px; width: 50px;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>M</option>
                            </select>
                            
                            <span style="color: #999; text-align: center;">×</span>
                            
                            <!-- Lebar -->
                            <input type="text" id="dimension_width_input" value="{{ old('dimension_width', $sand->dimension_width) }}" placeholder="L" style="padding: 7px 8px; border: 1px solid #999; border-radius: 2px; font-size: 13px; width: 100%;">
                            <select id="dimension_width_unit" style="padding: 7px 4px; border: 1px solid #999; border-radius: 2px; font-size: 12px; width: 50px;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>M</option>
                            </select>
                            
                            <span style="color: #999; text-align: center;">×</span>
                            
                            <!-- Tinggi -->
                            <input type="text" id="dimension_height_input" value="{{ old('dimension_height', $sand->dimension_height) }}" placeholder="T" style="padding: 7px 8px; border: 1px solid #999; border-radius: 2px; font-size: 13px; width: 100%;">
                            <select id="dimension_height_unit" style="padding: 7px 4px; border: 1px solid #999; border-radius: 2px; font-size: 12px; width: 50px;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>M</option>
                            </select>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length', $sand->dimension_length) }}">
                        <input type="hidden" name="dimension_width" id="dimension_width" value="{{ old('dimension_width', $sand->dimension_width) }}">
                        <input type="hidden" name="dimension_height" id="dimension_height" value="{{ old('dimension_height', $sand->dimension_height) }}">
                        
                        <!-- Display hasil konversi -->
                        <div style="display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 6px; margin-top: 3px;">
                            <small style="color: #7f8c8d; font-size: 10px; text-align: left;">
                                Panjang <span id="length_m_display">{{ $sand->dimension_length ? number_format($sand->dimension_length, 2, '.', '') : '-' }}</span> m
                            </small>
                            <span style="width: 10px"></span>
                            <small style="color: #7f8c8d; font-size: 10px; text-align: left;">
                                Lebar <span id="width_m_display">{{ $sand->dimension_width ? number_format($sand->dimension_width, 2, '.', '') : '-' }}</span> m
                            </small>
                            <span style="width: 10px"></span>
                            <small style="color: #7f8c8d; font-size: 10px; text-align: left;">
                                Tinggi <span id="height_m_display">{{ $sand->dimension_height ? number_format($sand->dimension_height, 2, '.', '') : '-' }}</span> m
                            </small>
                        </div>
                    </div>
                </div>

                <div> 
                    <!-- Volume (Hasil Kalkulasi) -->
                    <div class="row">
                        <label>Volume Kemasan</label>
                        <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                            <span id="volume_display" style="font-weight: bold; color: #27ae60;">-</span> <span>M<span class="raise">3</span></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Harga Kemasan</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="package_price" id="package_price" value="{{ old('package_price', $sand->package_price) }}">
                        <input type="text" id="package_price_display" value="{{ old('package_price', $sand->package_price) }}" inputmode="numeric" placeholder="0" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <span style="padding: 0 4px;">/Kemasan</span>
                    </div>
                </div>

                <div class="row">
                    <label>Harga Komparasi</label>
                    <div style="display: flex; flex: 1; gap: 8px; align-items: center;">
                        <span style="margin-right: 5px; padding-top: 6px;">Rp</span>
                        <input type="hidden" name="comparison_price_per_m3" id="comparison_price_per_m3" value="{{ old('comparison_price_per_m3', $sand->comparison_price_per_m3) }}">
                        <input type="text" id="comparison_price_display" inputmode="numeric" placeholder="0" value="{{ $sand->comparison_price_per_m3 ? number_format($sand->comparison_price_per_m3, 0, ',', '.') : '' }}" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px; max-width: 83.5%;">
                        <span style="padding: 0 4px;">/M<span class="raise">3</span></span>
                    </div>
                </div>

                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="store" id="store" value="{{ old('store', $sand->store) }}" class="autocomplete-input" data-field="store" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Singkat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="short_address" id="short_address" value="{{ old('short_address', $sand->short_address) }}" class="autocomplete-input" data-field="short_address" autocomplete="off" placeholder="Contoh: Roxy, CitraLand, dsb" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="short_address-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat Lengkap</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="address" id="address" value="{{ old('address', $sand->address) }}" class="autocomplete-input" data-field="address" placeholder="Alamat lengkap toko" autocomplete="off" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: foto -->
            <div style="flex: 0 0 calc(35% - 20px); max-width: calc(35% - 20px);">
                <div class="right" id="photoPreviewArea" style="border: 1px solid #999; height: 380px; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; cursor: pointer; position: relative; overflow: hidden; width: 100%;">
                    @if($sand->photo_url)
                        <div id="photoPlaceholder" style="display:none;text-align: center; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                            <div>Klik untuk upload foto</div>
                            <div style="font-size: 12px; margin-top: 5px;">JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        <img id="photoPreview" src="{{ $sand->photo_url }}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
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
                    <span style="cursor: pointer; {{ $sand->photo_url ? '' : 'display:none;' }}" id="deletePhotoBtn">✕ Hapus</span>
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
    .raise { font-size: 0.7em; vertical-align: super; }
</style>

<script src="/js/sand-form.js"></script>
<script>
    if (typeof initSandForm === 'function') {
        initSandForm();
    }
</script>