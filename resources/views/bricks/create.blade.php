<div class="card">
    @if($errors->any())
        <div class="alert alert-danger">
            <div>
                <strong>Terdapat kesalahan pada input:</strong>
                <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('bricks.store') }}" method="POST" enctype="multipart/form-data" id="brickForm">
        @csrf

        <div class="form-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; max-width: 1100px; width: 100%;">

            <!-- Kolom Kiri - Form Fields -->
            <div class="left-column">

                <!-- Jenis -->
                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="type"
                               id="type"
                               value="{{ old('type') }}"
                               class="autocomplete-input"
                               data-field="type"
                               autocomplete="off"
                               placeholder="Pilih atau ketik jenis...">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <!-- Merek -->
                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="brand"
                               id="brand"
                               value="{{ old('brand') }}"
                               class="autocomplete-input"
                               data-field="brand"
                               autocomplete="off"
                               placeholder="Pilih atau ketik merek...">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <!-- Bentuk -->
                <div class="row">
                    <label>Bentuk</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="form"
                               id="form"
                               value="{{ old('form') }}"
                               class="autocomplete-input"
                               data-field="form"
                               autocomplete="off"
                               placeholder="Pilih atau ketik bentuk...">
                        <div class="autocomplete-list" id="form-list"></div>
                    </div>
                </div>

                <!-- Dimensi (P × L × T = Volume) -->
                <div class="row" style="align-items: flex-start; margin-top: 10px;">
                    <label style="padding-top: 28px;">Dimensi</label>
                    <div style="flex: 1;">
                        <div class="dimensi-wrapper" style="display: flex; align-items: flex-end; gap: 8px;">
                            <!-- Panjang -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Panjang</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="number"
                                           id="dimension_length_input"
                                           class="autocomplete-input"
                                           data-field="dimension_length"
                                           step="0.01"
                                           placeholder="0"
                                           autocomplete="off">
                                    <select id="dimension_length_unit" class="unit-selector">
                                        <option value="mm">mm</option>
                                        <option value="cm" selected>cm</option>
                                        <option value="m">M</option>
                                        <option value="inch">"</option>
                                    </select>
                                </div>
                                <div class="autocomplete-list" id="dimension_length-list"></div>
                            </div>

                            <span style="text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">×</span>

                            <!-- Lebar -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Lebar</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="number"
                                           id="dimension_width_input"
                                           class="autocomplete-input"
                                           data-field="dimension_width"
                                           step="0.01"
                                           placeholder="0"
                                           autocomplete="off">
                                    <select id="dimension_width_unit" class="unit-selector">
                                        <option value="mm">mm</option>
                                        <option value="cm" selected>cm</option>
                                        <option value="m">M</option>
                                        <option value="inch">"</option>
                                    </select>
                                </div>
                                <div class="autocomplete-list" id="dimension_width-list"></div>
                            </div>

                            <span style="text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">×</span>

                            <!-- Tinggi -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Tinggi</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="number"
                                           id="dimension_height_input"
                                           class="autocomplete-input"
                                           data-field="dimension_height"
                                           step="0.01"
                                           placeholder="0"
                                           autocomplete="off">
                                    <select id="dimension_height_unit" class="unit-selector">
                                        <option value="mm">mm</option>
                                        <option value="cm" selected>cm</option>
                                        <option value="m">M</option>
                                        <option value="inch">"</option>
                                    </select>
                                </div>
                                <div class="autocomplete-list" id="dimension_height-list"></div>
                            </div>

                            <span style="text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">=</span>

                            <!-- Volume -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px; font-weight: 700;">Volume</span>
                                <div class="dimensi-input-with-unit" style="position: relative;">
                                    <input type="number"
                                           id="volume_display_input"
                                           readonly
                                           placeholder="0"
                                           style="text-align: right; padding-right: 38px;">
                                    <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; font-weight: 600; pointer-events: none;">M3</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length') }}">
                        <input type="hidden" name="dimension_width" id="dimension_width" value="{{ old('dimension_width') }}">
                        <input type="hidden" name="dimension_height" id="dimension_height" value="{{ old('dimension_height') }}">
                        <input type="hidden" name="package_volume" id="package_volume" value="{{ old('package_volume') }}">
                    </div>
                </div>

                <!-- Harga (Harga per Buah + Harga Komparasi dalam satu baris) -->
                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Harga</label>
                    <div style="flex: 1; display: flex; gap: 15px; align-items: stretch;">
                        <!-- Harga per Buah -->
                        <div class="flex-fill" style="flex: 1; display: flex; align-items: stretch; position: relative;">
                            <input type="hidden" name="price_per_piece" id="price_per_piece" value="{{ old('price_per_piece') }}">
                            <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                <span style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; pointer-events: none; z-index: 1;">Rp</span>
                                <input type="text"
                                       id="price_per_piece_display"
                                       value="{{ old('price_per_piece') }}"
                                       class="autocomplete-input"
                                       data-field="price_per_piece"
                                       inputmode="numeric"
                                       placeholder="0"
                                       autocomplete="off"
                                       style="width: 100%; height: 100%; padding: 10px 60px 10px 38px; font-size: 14px;">
                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none;">/ Buah</span>
                            </div>
                            <div class="autocomplete-list" id="price_per_piece-list"></div>
                        </div>

                        <!-- Harga Komparasi -->
                        <div class="mini-input-wrapper flex-fill" style="display: flex; flex-direction: column; flex: 1; min-width: 0;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px;">Harga Komparasi</span>
                            <div style="display: flex; align-items: center; position: relative;">
                                <input type="hidden" name="comparison_price_per_m3" id="comparison_price_per_m3" value="{{ old('comparison_price_per_m3') }}">
                                <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                    <span style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; pointer-events: none; z-index: 1;">Rp</span>
                                    <input type="text"
                                           id="comparison_price_display"
                                           class="autocomplete-input"
                                           data-field="comparison_price_per_m3"
                                           inputmode="numeric"
                                           placeholder="0"
                                           autocomplete="off"
                                           style="width: 100%; height: 38px; padding: 10px 50px 10px 38px; font-size: 14px;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none;">/ M3</span>
                                </div>
                                <div class="autocomplete-list" id="comparison_price_per_m3-list"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toko -->
                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="store"
                               id="store"
                               value="{{ old('store') }}"
                               class="autocomplete-input"
                               data-field="store"
                               autocomplete="off"
                               placeholder="Pilih atau ketik nama toko...">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <!-- Alamat Lengkap -->
                <div class="row">
                    <label>Alamat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="address"
                               id="address"
                               value="{{ old('address') }}"
                               class="autocomplete-input"
                               data-field="address"
                               autocomplete="off"
                               placeholder="Alamat lengkap toko...">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan - Gambar -->
            <div class="image-section" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="image-preview-box" id="photoPreviewArea"
                         style="width: 100%;
                                min-height: 200px;
                                max-height: 400px;
                                height: 320px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                                position: relative;
                                overflow: hidden;
                                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                        <div id="photoPlaceholder" style="text-align: center;">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Foto</div>
                        </div>
                        <img id="photoPreview"
                             src=""
                             alt="Preview"
                             style="display: none; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                    </div>

                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">

                    <div class="image-actions" style="margin-top: 5px; display: flex; justify-content: center; font-weight: bold; font-size: 14px; padding: 0 10px; gap: 10px;">
                        <span class="text-upload" id="uploadBtn" style="cursor: pointer;"><i class="bi bi-upload"></i> Upload</span>
                        <span class="text-delete" id="deletePhotoBtn" style="cursor: pointer; display: none;"><i class="bi bi-trash"></i> Hapus</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; justify-content: center; gap: 20px; padding-bottom: 15px;">
                    <button type="button" class="btn-cancel"
                          onclick="if(typeof window.closeFloatingModal === 'function'){ window.closeFloatingModal(); }">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </div>
        </div>

    </form>
</div>

<script src="/js/brick-form.js?v={{ time() }}"></script>
<script>
    if (typeof initBrickForm === 'function') {
        initBrickForm();
    }
</script>
