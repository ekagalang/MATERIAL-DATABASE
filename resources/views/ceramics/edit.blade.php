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

    <form action="{{ route('ceramics.update', $ceramic->id) }}" method="POST" enctype="multipart/form-data" id="ceramicForm">
        @csrf
        @method('PUT')

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
                               value="{{ old('type', $ceramic->type) }}"
                               class="autocomplete-input"
                               data-field="type"
                               autocomplete="off"
                               placeholder="Lantai, Dinding, Granit...">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <!-- Dimensi (Panjang × Lebar grouped, Tebal, Luas) -->
                <div class="row" style="align-items: flex-start; margin-top: 15px;">
                    <label style="padding-top: 18px;">Dimensi</label>
                    @php
                        $lengthValue = old('dimension_length', $ceramic->dimension_length);
                        $lengthValue = ($lengthValue === null || $lengthValue === '') ? '' : (string) $lengthValue;
                        if ($lengthValue !== '' && strpos($lengthValue, '.') !== false) {
                            $lengthValue = rtrim(rtrim($lengthValue, '0'), '.');
                        }

                        $widthValue = old('dimension_width', $ceramic->dimension_width);
                        $widthValue = ($widthValue === null || $widthValue === '') ? '' : (string) $widthValue;
                        if ($widthValue !== '' && strpos($widthValue, '.') !== false) {
                            $widthValue = rtrim(rtrim($widthValue, '0'), '.');
                        }
                    @endphp
                    <div class="dimensi-wrapper" style="display: flex; align-items: flex-end; gap: 15px; width: 100%;">
                        <!-- Group: Panjang × Lebar -->
                        <div style="display: flex; align-items: flex-end; gap: 5px; flex: 2;">
                            <!-- Panjang -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Panjang</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="text" inputmode="decimal"
                                           id="dimension_length_input"
                                           class="autocomplete-input"
                                           data-field="dimension_length"
                                           step="0.01"
                                           value="{{ $lengthValue }}"
                                           placeholder="0"
                                           autocomplete="off"
                                           required
                                           style="text-align: right;">
                                    <select id="dimension_length_unit" class="unit-selector">
                                        <option value="mm">mm</option>
                                        <option value="cm" selected>cm</option>
                                        <option value="m">M</option>
                                    </select>
                                </div>
                                <div class="autocomplete-list" id="dimension_length-list"></div>
                            </div>

                            <span class="math-symbol" style="font-weight: bold; font-size: 16px; padding-bottom: 8px;">×</span>

                            <!-- Lebar -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Lebar</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="text" inputmode="decimal"
                                           id="dimension_width_input"
                                           class="autocomplete-input"
                                           data-field="dimension_width"
                                           step="0.01"
                                           value="{{ $widthValue }}"
                                           placeholder="0"
                                           autocomplete="off"
                                           required
                                           style="text-align: right;">
                                    <select id="dimension_width_unit" class="unit-selector">
                                        <option value="mm">mm</option>
                                        <option value="cm" selected>cm</option>
                                        <option value="m">M</option>
                                    </select>
                                </div>
                                <div class="autocomplete-list" id="dimension_width-list"></div>
                            </div>
                        </div>

                        <!-- Tebal (Thickness) -->
                        <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 0.8; position: relative;">
                            <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Tebal</span>
                            @php
                                $thicknessCm = old('dimension_thickness', $ceramic->dimension_thickness);
                                $thicknessMm = ($thicknessCm !== null && $thicknessCm !== '') ? ((float) $thicknessCm * 10) : '';
                                $thicknessMm = ($thicknessMm === '' || $thicknessMm === null) ? '' : (string) $thicknessMm;
                                if ($thicknessMm !== '' && strpos($thicknessMm, '.') !== false) {
                                    $thicknessMm = rtrim(rtrim($thicknessMm, '0'), '.');
                                }
                            @endphp
                            <div class="dimensi-input-with-unit">
                                <input type="text" inputmode="decimal"
                                       id="dimension_thickness_input"
                                       class="autocomplete-input"
                                       data-field="dimension_thickness"
                                       step="0.01"
                                       value="{{ $thicknessMm }}"
                                       placeholder="0"
                                       autocomplete="off"
                                       style="text-align: right;">
                                <select id="dimension_thickness_unit" class="unit-selector">
                                    <option value="mm" selected>mm</option>
                                    <option value="cm">cm</option>
                                </select>
                            </div>
                            <input type="hidden" name="dimension_thickness" id="dimension_thickness" value="{{ $thicknessCm }}">
                            <div class="autocomplete-list" id="dimension_thickness-list"></div>
                        </div>

                        <!-- Luas (Area per Piece - calculated) -->
                        <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1.2;">
                            <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Luas</span>
                            <div style="position: relative;">
                                <input type="text"
                                       id="area_per_piece_display"
                                       placeholder="0"
                                       readonly
                                       style="width: 100%; text-align: right; background: #f8fafc; cursor: not-allowed; padding-right: 65px;">
                                <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">M2 / Lbr</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs for actual dimension values -->
                    <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length', $ceramic->dimension_length) }}">
                    <input type="hidden" name="dimension_width" id="dimension_width" value="{{ old('dimension_width', $ceramic->dimension_width) }}">
                </div>

                <!-- Merek -->
                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="brand"
                               id="brand"
                               value="{{ old('brand', $ceramic->brand) }}"
                               class="autocomplete-input"
                               data-field="brand"
                               autocomplete="off"
                               placeholder="Pilih atau ketik merek..."
                               required>
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <!-- Sub Merek -->
                <div class="row">
                    <label>Sub Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="sub_brand"
                               id="sub_brand"
                               value="{{ old('sub_brand', $ceramic->sub_brand) }}"
                               class="autocomplete-input"
                               data-field="sub_brand"
                               autocomplete="off"
                               placeholder="Sub merek / seri">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <!-- Permukaan -->
                <div class="row">
                    <label>Permukaan</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="surface"
                               id="surface"
                               value="{{ old('surface', $ceramic->surface) }}"
                               class="autocomplete-input"
                               data-field="surface"
                               autocomplete="off"
                               placeholder="Glossy, Matte, dll...">
                        <div class="autocomplete-list" id="surface-list"></div>
                    </div>
                </div>

                <!-- Kode & Warna (dalam satu baris) -->
                <div class="row" style="align-items: flex-start;">
                    <label style="padding-top: 10px;">Nomor Seri / Corak</label>
                    <div style="flex: 1; display: flex; gap: 10px;">
                        <!-- Kode -->
                        <div style="flex: 1; position: relative;">
                            <input type="text"
                                   name="code"
                                   id="code"
                                   value="{{ old('code', $ceramic->code) }}"
                                   class="autocomplete-input"
                                   data-field="code"
                                   autocomplete="off"
                                   placeholder="Kode produk">
                            <div class="autocomplete-list" id="code-list"></div>
                        </div>
                        <!-- Warna -->
                        <div style="flex: 1; position: relative;">
                            <input type="text"
                                   name="color"
                                   id="color"
                                   value="{{ old('color', $ceramic->color) }}"
                                   class="autocomplete-input"
                                   data-field="color"
                                   autocomplete="off"
                                   placeholder="Warna / motif">
                            <div class="autocomplete-list" id="color-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Bentuk -->
                <div class="row">
                    <label>Bentuk</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="form"
                               id="form"
                               value="{{ old('form', $ceramic->form) }}"
                               class="autocomplete-input"
                               data-field="form"
                               autocomplete="off"
                               placeholder="Persegi, Hexagon...">
                        <div class="autocomplete-list" id="form-list"></div>
                    </div>
                </div>

                <!-- Kemasan (Packaging + Volume) -->
                <div class="row" style="align-items: flex-start; margin-top: 15px;">
                    <label style="padding-top: 10px;">Kemasan</label>
                    <div style="flex: 1; display: flex; gap: 10px;">
                        <!-- Packaging Type -->
                        <div style="flex: 1; display: flex; align-items: stretch;">
                            <select name="packaging" id="packaging" style="width: 100%; height: 100%;">
                                <option value="" selected>-- Pilih --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->name }}" {{ old('packaging', $ceramic->packaging ?? 'Dus') == $unit->name ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Volume (Pieces per Package) -->
                        <div style="flex: 2; display: flex; flex-direction: column;">
                            <span style="font-size: 13px; font-style: italic; margin-bottom: 4px;">Isi per Kemasan</span>
                            <div style="position: relative;">
                                <input type="text" inputmode="decimal"
                                       name="pieces_per_package"
                                       id="pieces_per_package"
                                       class="autocomplete-input"
                                       data-field="pieces_per_package"
                                       value="{{ old('pieces_per_package', $ceramic->pieces_per_package) }}"
                                       placeholder="0"
                                       min="1"
                                       required
                                       style="width: 100%; padding-right: 85px; text-align: right;">
                                <span id="volume_suffix" class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none; white-space: nowrap;">Lbr / -</span>
                                <div class="autocomplete-list" id="pieces_per_package-list"></div>
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
                               value="{{ old('store', $ceramic->store) }}"
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
                               value="{{ old('address', $ceramic->address) }}"
                               class="autocomplete-input"
                               data-field="address"
                               autocomplete="off"
                               placeholder="Alamat lengkap toko...">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

                <!-- Harga (Harga per Dus + Harga Komparasi) -->
                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Harga</label>
                    <div style="flex: 1; display: flex; gap: 15px; align-items: stretch;">
                        <!-- Harga per Dus -->
                        <div class="flex-fill" style="flex: 1; display: flex; align-items: stretch; position: relative;">
                            <input type="hidden" name="price_per_package" id="price_per_package" value="{{ old('price_per_package', $ceramic->price_per_package) }}">
                            <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; pointer-events: none; z-index: 1;">Rp</span>
                                <input type="text"
                                       id="price_per_package_display"
                                       value="{{ old('price_per_package', $ceramic->price_per_package) }}"
                                       class="autocomplete-input"
                                       data-field="price_per_package"
                                       inputmode="numeric"
                                       placeholder="0"
                                       autocomplete="off"
                                       required
                                       style="width: 100%; height: 100%; padding: 10px 60px 10px 38px; font-size: 14px;">
                                <span id="price_unit_display_inline" class="unit-inside price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ -</span>
                            </div>
                            <div class="autocomplete-list" id="price_per_package-list"></div>
                        </div>

                        <!-- Harga Komparasi -->
                        <div class="mini-input-wrapper flex-fill" style="display: flex; flex-direction: column; flex: 1; min-width: 0;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px;">Harga Komparasi</span>
                            <div style="display: flex; align-items: center; position: relative;">
                                <input type="hidden" name="comparison_price_per_m2" id="comparison_price_per_m2" value="{{ old('comparison_price_per_m2', $ceramic->comparison_price_per_m2) }}">
                                <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                    <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; pointer-events: none; z-index: 1;">Rp</span>
                                    <input type="text"
                                           id="comparison_price_display"
                                           class="autocomplete-input"
                                           data-field="comparison_price_per_m2"
                                           inputmode="numeric"
                                           placeholder="0"
                                           value="{{ old('comparison_price_per_m2', $ceramic->comparison_price_per_m2) }}"
                                           autocomplete="off"
                                           style="width: 100%; height: 38px; padding: 10px 50px 10px 38px; font-size: 14px;">
                                    <span class="price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none;">/ M2</span>
                                </div>
                                <div class="autocomplete-list" id="comparison_price_per_m2-list"></div>
                            </div>
                        </div>
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
                        <div id="photoPlaceholder" style="text-align: center; {{ $ceramic->photo ? 'display: none;' : '' }}">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Foto Keramik</div>
                        </div>
                        <img id="photoPreview"
                             src="{{ $ceramic->photo_url }}"
                             alt="Preview"
                             style="{{ $ceramic->photo ? '' : 'display: none;' }} max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                    </div>

                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">

                    <div class="image-actions" style="margin-top: 5px; display: flex; justify-content: center; font-weight: bold; font-size: 14px; padding: 0 10px; gap: 10px;">
                        <span class="text-upload" id="uploadBtn" style="cursor: pointer;"><i class="bi bi-upload"></i> Upload</span>
                        <span class="text-delete" id="deletePhotoBtn" style="cursor: pointer; {{ $ceramic->photo ? '' : 'display: none;' }}"><i class="bi bi-trash"></i> Hapus</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; justify-content: center; gap: 20px; padding-bottom: 15px;">
                    <button type="button" class="btn-cancel"
                          onclick="if(typeof window.closeFloatingModal === 'function'){ window.closeFloatingModal(); }">Batal</button>
                    <button type="submit" class="btn-save">Update</button>
                </div>
            </div>
        </div>

    </form>
</div>

<script src="/js/ceramic-form.js?v={{ time() }}"></script>
<script src="{{ asset('js/store-autocomplete.js') }}?v={{ time() }}"></script>
<script>
    if (typeof initCeramicForm === 'function') {
        initCeramicForm();
    }
    // Initialize store autocomplete after form init
    if (typeof initStoreAutocomplete === 'function') {
        initStoreAutocomplete(document.getElementById('ceramicForm')?.parentElement);
    }
</script>

