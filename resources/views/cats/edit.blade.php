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

    <form action="{{ route('cats.update', $cat->id) }}" method="POST" enctype="multipart/form-data" id="catForm">
        @csrf
        @method('PUT')

        {{-- Nama cat disembunyikan, akan diisi otomatis --}}
        <input type="hidden" name="cat_name" id="cat_name" value="{{ old('cat_name', $cat->cat_name) }}">

        <div class="form-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; max-width: 1100px; width: 100%;">

            <!-- Kolom Kiri - Form Fields -->
            <div class="left-column">

                <!-- Jenis -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Jenis</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; position: relative;">
                        <input type="text"
                               name="type"
                               id="type"
                               value="{{ old('type', $cat->type) }}"
                               class="autocomplete-input"
                               data-field="type"
                               autocomplete="off"
                               placeholder="Pilih atau ketik jenis cat..."
                               style="width: 100%;">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <!-- Merek -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Merek</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; position: relative;">
                        <input type="text"
                               name="brand"
                               id="brand"
                               value="{{ old('brand', $cat->brand) }}"
                               class="autocomplete-input"
                               data-field="brand"
                               autocomplete="off"
                               placeholder="Pilih atau ketik merek..."
                               style="width: 100%;">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <!-- Sub Merek -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Sub Merek</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; position: relative;">
                        <input type="text"
                               name="sub_brand"
                               id="sub_brand"
                               value="{{ old('sub_brand', $cat->sub_brand) }}"
                               class="autocomplete-input"
                               data-field="sub_brand"
                               autocomplete="off"
                               placeholder="Pilih atau ketik sub merek..."
                               style="width: 100%;">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <!-- Warna -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Warna</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%;">
                        <div style="flex: 1; min-width: 0; position: relative;">
                            <input type="text"
                                   name="color_code"
                                   id="color_code"
                                   value="{{ old('color_code', $cat->color_code) }}"
                                   class="autocomplete-input"
                                   data-field="color_code"
                                   autocomplete="off"
                                   placeholder="Kode Warna"
                                   style="width: 100%;">
                            <div class="autocomplete-list" id="color_code-list"></div>
                        </div>
                        <div style="flex: 1; min-width: 0; position: relative;">
                            <input type="text"
                                   name="color_name"
                                   id="color_name"
                                   value="{{ old('color_name', $cat->color_name) }}"
                                   class="autocomplete-input"
                                   data-field="color_name"
                                   autocomplete="off"
                                   placeholder="Nama Warna"
                                   style="width: 100%;">
                            <div class="autocomplete-list" id="color_name-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Kemasan -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: stretch; margin-top: 15px;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0; padding-top: 10px;">Kemasan</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%;">
                        <div style="flex: 1; min-width: 0;">
                            <select name="package_unit"
                                    id="package_unit"
                                    style="height: 100%; width: 100%;">
                                <option value="">-- Galon, Pail, Kaleng --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->code }}"
                                            data-weight="{{ $unit->package_weight }}"
                                            {{ old('package_unit', $cat->package_unit) == $unit->code ? 'selected' : '' }}>
                                        {{ $unit->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mini-input-wrapper" style="display: flex; flex-direction: column; width: 100%; flex: 1; min-width: 0; position: relative;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; white-space: nowrap;">Berat Kotor (Kg)</span>
                            <div style="position: relative;">
                                <input type="text"
                                       name="package_weight_gross"
                                       id="package_weight_gross"
                                       value="{{ old('package_weight_gross', $cat->package_weight_gross) }}"
                                       class="autocomplete-input"
                                       data-field="package_weight_gross"
                                       inputmode="decimal"
                                       placeholder="0.00"
                                       autocomplete="off"
                                       style="width: 100%; padding-right: 35px; text-align: right;">
                                <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: inherit; pointer-events: none;">Kg</span>
                            </div>
                            <div class="autocomplete-list" id="package_weight_gross-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Isi Bersih -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: flex-start; margin-top: 15px;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0; padding-top: 18px;">Isi Bersih</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%;">
                        <div class="mini-input-wrapper" style="display: flex; flex-direction: column; width: 100%; flex: 1; min-width: 0; position: relative;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; white-space: nowrap;">Volume (Liter)</span>
                            <div style="position: relative;">
                                <input type="text"
                                       name="volume"
                                       id="volume"
                                       value="{{ old('volume', $cat->volume) }}"
                                       class="autocomplete-input"
                                       data-field="volume"
                                       inputmode="decimal"
                                       placeholder="0.00"
                                       autocomplete="off"
                                       style="width: 100%; padding-right: 30px; text-align: right;">
                                <input type="hidden" name="volume_unit" id="volume_unit" value="L">
                                <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: inherit; pointer-events: none;">L</span>
                            </div>
                            <div class="autocomplete-list" id="volume-list"></div>
                        </div>

                        <div class="mini-input-wrapper" style="display: flex; flex-direction: column; width: 100%; flex: 1; min-width: 0; position: relative;">
                            <span class="mini-label" id="net_weight_label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; white-space: nowrap;">Berat Bersih (Kg)</span>
                            <div style="position: relative;">
                                <input type="text"
                                       name="package_weight_net"
                                       id="package_weight_net"
                                       value="{{ old('package_weight_net', $cat->package_weight_net) }}"
                                       class="autocomplete-input"
                                       data-field="package_weight_net"
                                       inputmode="decimal"
                                       placeholder="0.00"
                                       autocomplete="off"
                                       style="width: 100%; padding-right: 35px; text-align: right;">
                                <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: inherit; pointer-events: none;">Kg</span>
                            </div>
                            <div class="autocomplete-list" id="package_weight_net-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Toko -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Toko</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; position: relative;">
                        <input type="text"
                               name="store"
                               id="store"
                               value="{{ old('store', $cat->store) }}"
                               class="autocomplete-input"
                               data-field="store"
                               autocomplete="off"
                               placeholder="Pilih atau ketik nama toko..."
                               style="width: 100%;">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <!-- Alamat Lengkap -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: center;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0;">Alamat</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; position: relative;">
                        <input type="text"
                               name="address"
                               id="address"
                               value="{{ old('address', $cat->address) }}"
                               class="autocomplete-input"
                               data-field="address"
                               autocomplete="off"
                               placeholder="Alamat lengkap toko..."
                               style="width: 100%;">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

                <!-- Harga -->
                <div class="row" style="display: flex; margin-bottom: 15px; align-items: stretch; margin-top: 15px;">
                    <label style="width: 140px; font-weight: bold; font-size: 15px; flex-shrink: 0; padding-top: 10px;">Harga Beli</label>
                    <div class="input-group" style="flex-grow: 1; display: flex; gap: 15px; width: 100%; align-items: stretch;">
                        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; position: relative;">
                            <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $cat->purchase_price) }}">
                            <input type="hidden" name="price_unit" id="price_unit" value="{{ old('price_unit', $cat->price_unit) }}">
                            <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: inherit; pointer-events: none; z-index: 1;">Rp</span>
                                <input type="text"
                                       id="purchase_price_display"
                                       value="{{ old('purchase_price', $cat->purchase_price) }}"
                                       class="autocomplete-input"
                                       data-field="purchase_price"
                                       inputmode="numeric"
                                       placeholder="0"
                                       autocomplete="off"
                                       style="width: 100%; height: 100%; padding: 10px 70px 10px 38px; font-size: 14px;">
                                <span id="price_unit_display_inline" class="price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ {{ old('price_unit', $cat->price_unit) ?: '-' }}</span>
                            </div>
                            <div class="autocomplete-list" id="purchase_price-list"></div>
                        </div>

                        <div class="mini-input-wrapper" style="display: flex; flex-direction: column; width: 100%; flex: 1; min-width: 0;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; white-space: nowrap;">Harga Komparasi</span>
                            <div style="display: flex; align-items: center; position: relative;">
                                <input type="hidden" name="comparison_price_per_kg" id="comparison_price_per_kg" value="{{ old('comparison_price_per_kg', $cat->comparison_price_per_kg) }}">
                                <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                    <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: inherit; pointer-events: none; z-index: 1;">Rp</span>
                                    <input type="text"
                                           id="comparison_price_display"
                                           value="{{ $cat->comparison_price_per_kg ? \App\Helpers\NumberHelper::format($cat->comparison_price_per_kg) : '' }}"
                                           class="autocomplete-input"
                                           data-field="comparison_price_per_kg"
                                           inputmode="numeric"
                                           placeholder="0"
                                           autocomplete="off"
                                           style="width: 100%; height: 38px; padding: 10px 50px 10px 38px; font-size: 14px;">
                                    <span class="price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ Kg</span>
                                </div>
                                <div class="autocomplete-list" id="comparison_price_per_kg-list"></div>
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
                                background-color: #ffffff;
                                border: 2px dashed #e2e8f0;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: #cbd5e1;
                                cursor: pointer;
                                position: relative;
                                overflow: hidden;
                                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                        @if($cat->photo_url)
                            <div id="photoPlaceholder" style="display: none; text-align: center;">
                                <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                                <div style="font-size: 14px; font-weight: 600; color: inherit; margin-bottom: 6px;">Foto</div>
                            </div>
                            <img id="photoPreview"
                                 src="{{ $cat->photo_url }}"
                                 alt="Preview"
                                 style="display: block; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                        @else
                            <div id="photoPlaceholder" style="text-align: center;">
                                <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                                <div style="font-size: 14px; font-weight: 600; color: inherit; margin-bottom: 6px;">Foto</div>
                            </div>
                            <img id="photoPreview"
                                 src=""
                                 alt="Preview"
                                 style="display: none; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                        @endif
                    </div>

                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">

                    <div class="image-actions" style="margin-top: 5px; display: flex; justify-content: center; font-weight: bold; font-size: 14px; padding: 0 10px; gap: 10px;">
                        <span class="text-upload" id="uploadBtn" style="color: #5cb85c; cursor: pointer;"><i class="bi bi-upload"></i> Upload</span>
                        <span class="text-delete" id="deletePhotoBtn" style="color: #d9534f; cursor: pointer; display: {{ $cat->photo_url ? 'inline' : 'none' }};"><i class="bi bi-trash"></i> Hapus</span>
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

<style>
    /* Autocomplete styling */
    .autocomplete-list {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: #fff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-top: none !important;
        border-radius: 0 0 10px 10px !important;
        max-height: 240px !important;
        overflow-y: auto !important;
        z-index: 10000 !important;
        width: 100% !important;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08) !important;
        display: none !important;
        margin-top: -1px !important;
    }

    .autocomplete-item {
        padding: 12px 16px !important;
        cursor: pointer !important;
        border-bottom: 1px solid #f8fafc !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        font-size: 13.5px !important;
        color: #475569 !important;
    }

    .autocomplete-item:hover {
        background: linear-gradient(to right, #fef2f2 0%, #fef8f8 100%) !important;
        color: #891313 !important;
        padding-left: 20px !important;
    }

    .autocomplete-item:last-child {
        border-bottom: none !important;
    }

    /* Scrollbar */
    .autocomplete-list::-webkit-scrollbar {
        width: 6px;
    }

    .autocomplete-list::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 3px;
    }

    .autocomplete-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .autocomplete-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Input focus */
    .autocomplete-input:focus {
        border-color: #891313 !important;
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08) !important;
        background: #fffbfb !important;
    }

    /* All inputs focus effect (including price inputs) */
    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus {
        outline: 2px solid #2f80ed !important;
    }

    /* Container positioning */
    .row > div {
        position: relative;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .form-container {
            grid-template-columns: 1fr !important;
        }
        .row label {
            width: 100% !important;
            margin-bottom: 5px !important;
        }
        .row {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
    }
</style>

<script src="/js/cat-form.js?v=2.0.2"></script>
<script src="{{ asset('js/store-autocomplete.js') }}?v={{ time() }}"></script>
<script>
    if (typeof initCatForm === 'function') {
        initCatForm();
    }
    // Initialize store autocomplete after form init
    if (typeof initStoreAutocomplete === 'function') {
        initStoreAutocomplete(document.getElementById('catForm')?.parentElement);
    }
</script>
