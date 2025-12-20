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

    <form action="{{ route('cements.update', $cement->id) }}" method="POST" enctype="multipart/form-data" id="cementForm">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Kolom Kiri - Form Fields -->
            <div>

                {{-- Nama semen disembunyikan, akan dikirim apa adanya/otomatis --}}
                <input type="hidden" name="cement_name" id="cement_name" value="{{ old('cement_name', $cement->cement_name) }}">

                <!-- Jenis -->
                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" 
                               name="type" 
                               id="type" 
                               value="{{ old('type', $cement->type) }}" 
                               class="autocomplete-input" 
                               data-field="type" 
                               autocomplete="off" 
                               placeholder="Pilih atau ketik jenis semen...">
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
                               value="{{ old('brand', $cement->brand) }}" 
                               class="autocomplete-input" 
                               data-field="brand" 
                               autocomplete="off" 
                               placeholder="Pilih atau ketik merek...">
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
                               value="{{ old('sub_brand', $cement->sub_brand) }}" 
                               class="autocomplete-input" 
                               data-field="sub_brand" 
                               autocomplete="off" 
                               placeholder="Pilih atau ketik sub merek...">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <!-- Code & Warna (dalam satu row) -->
                <div class="row">
                    <label>Kode & Warna</label>
                    <div style="flex: 1; display: flex; gap: 15px;">
                        <!-- Code -->
                        <div style="flex: 1; position: relative;">
                            <input type="text"
                                   name="code"
                                   id="code"
                                   value="{{ old('code', $cement->code) }}"
                                   class="autocomplete-input"
                                   data-field="code"
                                   autocomplete="off"
                                   placeholder="Kode...">
                            <div class="autocomplete-list" id="code-list"></div>
                        </div>

                        <!-- Warna -->
                        <div style="flex: 1; position: relative;">
                            <input type="text"
                                   name="color"
                                   id="color"
                                   value="{{ old('color', $cement->color) }}"
                                   class="autocomplete-input"
                                   data-field="color"
                                   autocomplete="off"
                                   placeholder="Warna...">
                            <div class="autocomplete-list" id="color-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Kemasan -->
                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Kemasan</label>
                    <div style="flex: 1; display: flex; gap: 15px;">
                        <!-- Satuan Kemasan -->
                        <div style="flex: 2;">
                            <select name="package_unit"
                                    id="package_unit"
                                    style="width: 100%; height: 100%;">
                                <option value="">Sak, Karung</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->code }}"
                                            data-weight="{{ $unit->package_weight }}"
                                            {{ old('package_unit', $cement->package_unit) == $unit->code ? 'selected' : '' }}>
                                        {{ $unit->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Berat (Kg) with mini-label -->
                        <div class="mini-input-wrapper" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; color: #64748b;">Berat (Kg)</span>
                            <div style="position: relative;">
                                <input type="text"
                                    name="package_weight_gross"
                                    id="package_weight_gross"
                                    value="{{ old('package_weight_gross', $cement->package_weight_gross) }}"
                                    class="autocomplete-input"
                                    data-field="package_weight_gross"
                                    inputmode="decimal"
                                    placeholder="0"
                                    autocomplete="off"
                                    style="width: 100%; padding-right: 35px; text-align: right;">
                                <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #64748b; pointer-events: none;">Kg</span>
                            </div>
                            <div class="autocomplete-list" id="package_weight_gross-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Dimensi Kemasan (P A- L A- T = Volume) -->
                <div class="row" style="align-items: flex-start; margin-top: 10px;">
                    <label style="padding-top: 28px;">Dimensi Kemasan</label>
                    <div style="flex: 1;">
                        <div class="dimensi-wrapper" style="display: flex; align-items: flex-end; gap: 8px;">
                            <!-- Panjang -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px; color: #64748b;">Panjang</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="text"
                                           id="dimension_length_input"
                                           value="{{ old('dimension_length', $cement->dimension_length ? $cement->dimension_length * 100 : '') }}"
                                           class="autocomplete-input"
                                           data-field="dimension_length"
                                           inputmode="decimal"
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

                            <span style="color: #cbd5e1; text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">×</span>

                            <!-- Lebar -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px; color: #64748b;">Lebar</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="text"
                                           id="dimension_width_input"
                                           value="{{ old('dimension_width', $cement->dimension_width ? $cement->dimension_width * 100 : '') }}"
                                           class="autocomplete-input"
                                           data-field="dimension_width"
                                           inputmode="decimal"
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

                            <span style="color: #cbd5e1; text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">×</span>

                            <!-- Tinggi -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1; position: relative;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px; color: #64748b;">Tinggi</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="text"
                                           id="dimension_height_input"
                                           value="{{ old('dimension_height', $cement->dimension_height ? $cement->dimension_height * 100 : '') }}"
                                           class="autocomplete-input"
                                           data-field="dimension_height"
                                           inputmode="decimal"
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

                            <span style="color: #cbd5e1; text-align: center; font-weight: 300; font-size: 16px; padding-bottom: 10px;">=</span>

                            <!-- Volume -->
                            <div class="dimensi-item" style="display: flex; flex-direction: column; flex: 1;">
                                <span class="dimensi-label" style="font-style: italic; font-size: 13px; margin-bottom: 2px; font-weight: 700; color: #15803d;">Volume</span>
                                <div class="dimensi-input-with-unit">
                                    <input type="number"
                                           id="volume_display"
                                           readonly
                                           placeholder="0"
                                           value="{{ $cement->package_volume }}"
                                           style="text-align: right; padding-right: 38px; width: 100%; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); font-weight: 600; color: #15803d; border: 1.5px solid #86efac;">
                                    <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #16a34a; font-weight: 600; pointer-events: none;">M3</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs -->
                        <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length', $cement->dimension_length) }}">
                        <input type="hidden" name="dimension_width" id="dimension_width" value="{{ old('dimension_width', $cement->dimension_width) }}">
                        <input type="hidden" name="dimension_height" id="dimension_height" value="{{ old('dimension_height', $cement->dimension_height) }}">
                        <input type="hidden" name="package_volume" id="package_volume" value="{{ old('package_volume', $cement->package_volume) }}">
                    </div>
                </div>

                <!-- Berat Bersih (Kalkulasi)
                <div class="row">
                    <label>Berat Bersih (Kalkulasi)</label>
                    <div style="flex: 1;">
                        <div style="margin-top: 6px; padding: 8px 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #86efac; border-radius: 8px; display: inline-block;">
                            <small style="color: #15803d; font-size: 11px; font-weight: 600;">
                                Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight: 700; font-size: 12px;">{{ $cement->package_weight_net ? rtrim(rtrim(number_format($cement->package_weight_net, 2, ',', '.'), '0'), ',') . ' Kg' : '-' }}</span>
                            </small>
                        </div>
                    </div>
                </div>
                -->

                <!-- Harga (Harga Kemasan + Harga Komparasi dalam satu baris) -->
                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Harga</label>
                    <div style="flex: 1; display: flex; gap: 15px; align-items: stretch;">
                        <!-- Harga Kemasan -->
                        <div class="flex-fill" style="flex: 1; display: flex; align-items: stretch; position: relative;">
                            <input type="hidden" name="package_price" id="package_price" value="{{ old('package_price', $cement->package_price) }}">
                            <input type="hidden" name="price_unit" id="price_unit" value="{{ old('price_unit', $cement->price_unit) }}">
                            <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                <span style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: #64748b; pointer-events: none; z-index: 1;">Rp</span>
                                <input type="text"
                                       id="package_price_display"
                                       value="{{ old('package_price', $cement->package_price) }}"
                                       class="autocomplete-input"
                                       data-field="package_price"
                                       inputmode="numeric"
                                       placeholder="0"
                                       autocomplete="off"
                                       style="width: 100%; height: 100%; padding: 10px 70px 10px 38px; font-size: 14px;">
                                <span id="price_unit_display_inline" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ -</span>
                            </div>
                            <div class="autocomplete-list" id="package_price-list"></div>
                        </div>

                        <!-- Harga Komparasi per Kg -->
                        <div class="mini-input-wrapper flex-fill" style="display: flex; flex-direction: column; flex: 1; min-width: 0;">
                            <span class="mini-label" style="font-size: 13px; font-style: italic; margin-bottom: 4px; color: #64748b;">Harga Komparasi</span>
                            <div style="display: flex; align-items: center; position: relative;">
                                <input type="hidden" name="comparison_price_per_kg" id="comparison_price_per_kg" value="{{ old('comparison_price_per_kg', $cement->comparison_price_per_kg) }}">
                                <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                    <span style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: #64748b; pointer-events: none; z-index: 1;">Rp</span>
                                    <input type="text"
                                           id="comparison_price_display"
                                           class="autocomplete-input"
                                           data-field="comparison_price_per_kg"
                                           inputmode="numeric"
                                           placeholder="0"
                                           autocomplete="off"
                                           style="width: 100%; height: 38px; padding: 10px 50px 10px 38px; font-size: 14px;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ Kg</span>
                                </div>
                                <div class="autocomplete-list" id="comparison_price_per_kg-list"></div>
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
                               value="{{ old('store', $cement->store) }}" 
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
                               value="{{ old('address', $cement->address) }}" 
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
                    @if($cement->photo_url)
                        <div id="photoPlaceholder" style="display: none; text-align: center;">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Foto</div>
                        </div>
                        <img id="photoPreview" 
                             src="{{ $cement->photo_url }}" 
                             alt="Preview" 
                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                    @else
                        <div id="photoPlaceholder" style="text-align: center;">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Foto</div>
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
                    <span class="text-delete" id="deletePhotoBtn" style="color: #d9534f; cursor: pointer; display: none;"><i class="bi bi-trash"></i> Hapus</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; justify-content: center; gap: 20px; padding-bottom: 15px;">
                <button type="button" class="btn-cancel"
                      onclick="window.parent.document.getElementById('closeModal').click()">Batal</button>
                <button type="submit" class="btn-save">Update</button>
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

    /* Container positioning */
    .row > div {
        position: relative;
    }
</style>

<script src="/js/cement-form.js"></script>
<script>
    if (typeof initCementForm === 'function') {
        const currentForm = document.getElementById('cementForm');
        initCementForm(currentForm ? currentForm.parentElement : document);
    }
</script>
