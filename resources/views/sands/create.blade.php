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

    <form action="{{ route('sands.store') }}" method="POST" enctype="multipart/form-data" id="sandForm">
        @csrf

        <div style="display: flex; gap: 32px;">
            <!-- Kolom Kiri - Form Fields -->
            <div style="flex: 0 0 calc(65% - 16px); max-width: calc(65% - 16px);">

                {{-- Nama pasir disembunyikan, akan diisi otomatis --}}
                <input type="hidden" name="sand_name" id="sand_name" value="{{ old('sand_name') }}">

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
                               placeholder="Pilih atau ketik jenis pasir...">
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

                <!-- Kemasan -->
                <div class="row">
                    <label>Kemasan</label>
                    <div style="flex: 1;">
                        <select name="package_unit" 
                                id="package_unit" 
                                style="width: 100%;">
                            <option value="">-- Satuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}" 
                                        data-weight="{{ $unit->package_weight }}" 
                                        {{ old('package_unit') == $unit->code ? 'selected' : '' }}>
                                    {{ $unit->code }}
                                </option>
                            @endforeach
                        </select>
                        <div style="margin-top: 6px; padding: 8px 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #86efac; border-radius: 8px; display: inline-block;">
                            <small style="color: #15803d; font-size: 11px; font-weight: 600;">
                                Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight: 700; font-size: 12px;">-</span>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Dimensi Kemasan (P × L × T) -->
                <div class="row">
                    <label>Dimensi Kemasan</label>
                    <div style="flex: 1;">
                        <div style="display: grid; grid-template-columns: 1fr 60px 12px 1fr 60px 12px 1fr 60px; gap: 8px; align-items: center;">
                            <!-- Panjang -->
                            <input type="text" 
                                   id="dimension_length_input" 
                                   placeholder="Panjang" 
                                   style="padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                            <select id="dimension_length_unit" 
                                    style="padding: 10px 8px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 12.5px; cursor: pointer;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>m</option>
                            </select>
                            
                            <span style="color: #cbd5e1; text-align: center; font-weight: 300; font-size: 16px;">×</span>
                            
                            <!-- Lebar -->
                            <input type="text" 
                                   id="dimension_width_input" 
                                   placeholder="Lebar" 
                                   style="padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                            <select id="dimension_width_unit" 
                                    style="padding: 10px 8px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 12.5px; cursor: pointer;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>m</option>
                            </select>
                            
                            <span style="color: #cbd5e1; text-align: center; font-weight: 300; font-size: 16px;">×</span>
                            
                            <!-- Tinggi -->
                            <input type="text" 
                                   id="dimension_height_input" 
                                   placeholder="Tinggi" 
                                   style="padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                            <select id="dimension_height_unit" 
                                    style="padding: 10px 8px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 12.5px; cursor: pointer;">
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m" selected>m</option>
                            </select>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length') }}">
                        <input type="hidden" name="dimension_width" id="dimension_width" value="{{ old('dimension_width') }}">
                        <input type="hidden" name="dimension_height" id="dimension_height" value="{{ old('dimension_height') }}">
                        
                        <!-- Display hasil konversi -->
                        <div style="display: grid; grid-template-columns: 1fr 72px 1fr 72px 1fr; gap: 8px; margin-top: 6px;">
                            <small style="color: #94a3b8; font-size: 11px;">
                                <span id="length_m_display" style="font-weight: 600; color: #64748b;">-</span> m
                            </small>
                            <span></span>
                            <small style="color: #94a3b8; font-size: 11px;">
                                <span id="width_m_display" style="font-weight: 600; color: #64748b;">-</span> m
                            </small>
                            <span></span>
                            <small style="color: #94a3b8; font-size: 11px;">
                                <span id="height_m_display" style="font-weight: 600; color: #64748b;">-</span> m
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Volume Kemasan -->
                <div class="row">
                    <label>Volume</label>
                    <div style="flex: 1;">
                        <div style="padding: 10px 14px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #86efac; border-radius: 10px; display: inline-block; min-width: 120px;">
                            <span id="volume_display" style="font-weight: 700; color: #15803d; font-size: 14px;">-</span>
                            <span style="font-weight: 600; color: #16a34a; font-size: 13px;"> m³</span>
                        </div>
                    </div>
                </div>

                <!-- Harga per Kemasan -->
                <div class="row">
                    <label>Harga Kemasan</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="font-weight: 600; color: #64748b; font-size: 14px;">Rp</span>
                            <input type="hidden" name="package_price" id="package_price" value="{{ old('package_price') }}">
                            <input type="text" 
                                   id="package_price_display" 
                                   value="{{ old('package_price') }}" 
                                   inputmode="numeric" 
                                   placeholder="0" 
                                   style="flex: 1; max-width: 240px;">
                            <span style="color: #94a3b8; font-size: 13px;">/kemasan</span>
                        </div>
                    </div>
                </div>

                <!-- Harga Komparasi per m³ -->
                <div class="row">
                    <label>Harga/m³</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="font-weight: 600; color: #64748b; font-size: 14px;">Rp</span>
                            <input type="hidden" name="comparison_price_per_m3" id="comparison_price_per_m3" value="{{ old('comparison_price_per_m3') }}">
                            <input type="text" 
                                   id="comparison_price_display" 
                                   inputmode="numeric" 
                                   placeholder="0" 
                                   style="flex: 1; max-width: 240px;">
                            <span style="color: #94a3b8; font-size: 13px;">/m³</span>
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

                <!-- Alamat Singkat -->
                <div class="row">
                    <label>Alamat Singkat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" 
                               name="short_address" 
                               id="short_address" 
                               value="{{ old('short_address') }}" 
                               class="autocomplete-input" 
                               data-field="short_address" 
                               autocomplete="off" 
                               placeholder="Contoh: Roxy, CitraLand, Taman Semanggi">
                        <div class="autocomplete-list" id="short_address-list"></div>
                    </div>
                </div>

                <!-- Alamat Lengkap -->
                <div class="row">
                    <label>Alamat Lengkap</label>
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

            <!-- Kolom Kanan - Upload Foto -->
            <div style="flex: 0 0 calc(35% - 16px); max-width: calc(35% - 16px);">
                <div id="photoPreviewArea" 
                     style="border: 2px dashed #e2e8f0; 
                            height: 420px; 
                            border-radius: 16px; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%); 
                            cursor: pointer; 
                            position: relative; 
                            overflow: hidden; 
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                    <div id="photoPlaceholder" style="text-align: center; color: #cbd5e1;">
                        <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                        <div style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Upload Foto Produk</div>
                        <div style="font-size: 12px; color: #94a3b8;">JPG, PNG, GIF (Max 2MB)</div>
                    </div>
                    <img id="photoPreview" 
                         src="" 
                         alt="Preview" 
                         style="display: none; 
                                width: 100%; 
                                height: 100%; 
                                object-fit: cover;">
                </div>
                
                <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">
                
                <div class="uploadDel" style="margin-top: 14px; display: flex; gap: 20px; font-size: 13px;">
                    <span style="cursor: pointer; 
                                 color: #891313; 
                                 font-weight: 600; 
                                 display: flex; 
                                 align-items: center; 
                                 gap: 6px;" 
                          id="uploadBtn">
                        <i class="bi bi-upload"></i> Upload Foto
                    </span>
                    <span style="cursor: pointer; 
                                 color: #ef4444; 
                                 font-weight: 600; 
                                 display: none; 
                                 align-items: center; 
                                 gap: 6px;" 
                          id="deletePhotoBtn">
                        <i class="bi bi-trash"></i> Hapus Foto
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
            <button type="button" 
                    class="btn btn-secondary" 
                    onclick="window.parent.document.getElementById('closeModal').click()"
                    style="background: transparent; color: #64748b; border: 1.5px solid #e2e8f0; box-shadow: none;">
                <i class="bi bi-x-lg"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg"></i> Simpan Data
            </button>
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

<script src="/js/sand-form.js"></script>
<script>
    if (typeof initSandForm === 'function') {
        initSandForm();
    }
</script>