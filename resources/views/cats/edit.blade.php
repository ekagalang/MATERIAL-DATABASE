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

        <div style="display: flex; gap: 32px;">
            <!-- Kolom Kiri - Form Fields -->
            <div style="flex: 0 0 calc(65% - 16px); max-width: calc(65% - 16px);">

                {{-- Nama cat disembunyikan, akan dikirim apa adanya/otomatis --}}
                <input type="hidden" name="cat_name" id="cat_name" value="{{ old('cat_name', $cat->cat_name) }}">

                <!-- Jenis -->
                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" 
                               name="type" 
                               id="type" 
                               value="{{ old('type', $cat->type) }}" 
                               class="autocomplete-input" 
                               data-field="type" 
                               autocomplete="off" 
                               placeholder="Pilih atau ketik jenis cat...">
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
                               value="{{ old('brand', $cat->brand) }}" 
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
                               value="{{ old('sub_brand', $cat->sub_brand) }}" 
                               class="autocomplete-input" 
                               data-field="sub_brand" 
                               autocomplete="off" 
                               placeholder="Pilih atau ketik sub merek...">
                        <div class="autocomplete-list" id="sub_brand-list"></div>
                    </div>
                </div>

                <!-- Warna -->
                <div class="row">
                    <label>Warna</label>
                    <div style="flex: 1; display: flex; gap: 10px;">
                        <div style="position: relative; flex: 0 0 45%;">
                            <input type="text" 
                                   name="color_code" 
                                   id="color_code" 
                                   value="{{ old('color_code', $cat->color_code) }}" 
                                   class="autocomplete-input" 
                                   data-field="color_code" 
                                   autocomplete="off" 
                                   placeholder="Kode warna">
                            <div class="autocomplete-list" id="color_code-list"></div>
                        </div>
                        <div style="position: relative; flex: 1;">
                            <input type="text" 
                                   name="color_name" 
                                   id="color_name" 
                                   value="{{ old('color_name', $cat->color_name) }}" 
                                   class="autocomplete-input" 
                                   data-field="color_name" 
                                   autocomplete="off" 
                                   placeholder="Nama warna">
                            <div class="autocomplete-list" id="color_name-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Volume Isi -->
                <div class="row">
                    <label>Volume Isi</label>
                    <div style="flex: 1; display: flex; gap: 10px; align-items: center;">
                        <input type="number" 
                               name="volume" 
                               id="volume" 
                               value="{{ old('volume', $cat->volume) }}" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00" 
                               style="flex: 1; max-width: 180px;">
                        <div style="position: relative; flex: 0 0 45%;">
                            <input type="text" 
                                   name="volume_unit" 
                                   id="volume_unit" 
                                   value="{{ old('volume_unit', $cat->volume_unit) }}" 
                                   class="autocomplete-input" 
                                   data-field="volume_unit" 
                                   autocomplete="off" 
                                   placeholder="L, ml, gallon">
                            <div class="autocomplete-list" id="volume_unit-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Kemasan -->
                <div class="row">
                    <label>Kemasan</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <select name="package_unit" 
                                    id="package_unit" 
                                    style="flex: 0 0 120px;">
                                <option value="">-- Satuan --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->code }}" 
                                            data-weight="{{ $unit->package_weight }}" 
                                            {{ old('package_unit', $cat->package_unit) == $unit->code ? 'selected' : '' }}>
                                        {{ $unit->code }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" 
                                   name="package_weight_gross" 
                                   id="package_weight_gross" 
                                   value="{{ old('package_weight_gross', $cat->package_weight_gross) }}" 
                                   step="0.01" 
                                   min="0" 
                                   placeholder="Berat Kotor" 
                                   style="flex: 1;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Kg</span>
                            <input type="number" 
                                   name="package_weight_net" 
                                   id="package_weight_net" 
                                   value="{{ old('package_weight_net', $cat->package_weight_net) }}" 
                                   step="0.01" 
                                   min="0" 
                                   placeholder="Berat Bersih" 
                                   style="flex: 1;">
                            <span style="color: #64748b; font-size: 13px; font-weight: 500;">Kg</span>
                        </div>
                        <div style="margin-top: 6px; padding: 8px 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #86efac; border-radius: 8px; display: inline-block;">
                            <small style="color: #15803d; font-size: 11px; font-weight: 600;">
                                Berat Bersih (Kalkulasi): <span id="net_weight_display" style="font-weight: 700; font-size: 12px;">-</span>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Harga -->
                <div class="row">
                    <label>Harga</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="font-weight: 600; color: #64748b; font-size: 14px;">Rp</span>
                            <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $cat->purchase_price) }}">
                            <input type="text" 
                                   id="purchase_price_display" 
                                   value="{{ old('purchase_price', $cat->purchase_price) }}" 
                                   inputmode="numeric" 
                                   placeholder="0" 
                                   style="flex: 1; max-width: 200px;">
                            <div style="display: flex; gap: 4px; align-items: center; flex: 0 0 auto;">
                                <span style="color: #cbd5e1; font-size: 14px;">/</span>
                                <span id="price_unit_display" style="color: #94a3b8; font-size: 13px;">{{ old('price_unit', $cat->price_unit) ?: '-' }}</span>
                            </div>
                            <input type="hidden" name="price_unit" id="price_unit" value="{{ old('price_unit', $cat->price_unit) }}">
                        </div>
                    </div>
                </div>

                <!-- Harga Komparasi per Kg -->
                <div class="row">
                    <label>Harga / Kg</label>
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="font-weight: 600; color: #64748b; font-size: 14px;">Rp</span>
                            <input type="hidden" name="comparison_price_per_kg" id="comparison_price_per_kg" value="{{ old('comparison_price_per_kg', $cat->comparison_price_per_kg) }}">
                            <input type="text" 
                                   id="comparison_price_display" 
                                   value="{{ $cat->comparison_price_per_kg ? number_format($cat->comparison_price_per_kg, 0, ',', '.') : '' }}" 
                                   inputmode="numeric" 
                                   placeholder="0" 
                                   style="flex: 1; max-width: 200px;">
                            <span style="color: #94a3b8; font-size: 13px;">/ Kg</span>
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
                               value="{{ old('store', $cat->store) }}" 
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
                               value="{{ old('short_address', $cat->short_address) }}" 
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
                               value="{{ old('address', $cat->address) }}" 
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
                    @if($cat->photo_url)
                        <div id="photoPlaceholder" style="display: none; text-align: center; color: #cbd5e1;">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Upload Foto Produk</div>
                            <div style="font-size: 12px; color: #94a3b8;">JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        <img id="photoPreview" 
                             src="{{ $cat->photo_url }}" 
                             alt="Preview" 
                             style="width: 100%; 
                                    height: 100%; 
                                    object-fit: cover;">
                    @else
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
                    @endif
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
                                 display: {{ $cat->photo_url ? 'flex' : 'none' }}; 
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
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Update Data
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

<script src="/js/cat-form.js"></script>
<script>
    if (typeof initCatForm === 'function') {
        initCatForm();
    }
</script>