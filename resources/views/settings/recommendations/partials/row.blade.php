<div class="recommendation-card" data-index="{{ $index }}">
    <div class="card-header-row">
        <span class="card-title-text">
            <i class="bi bi-bookmark-star-fill text-warning me-2"></i>Rekomendasi #{{ is_numeric($index) ? $index + 1 : 'NEW' }}
        </span>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    </div>

    {{-- WORK TYPE HIDDEN INPUT (fixed per accordion) --}}
    <input type="hidden"
           name="recommendations[{{ $index }}][work_type]"
           value="{{ $workTypeCode ?? ($rec ? $rec->work_type : 'brick_half') }}">

    <div class="material-grid">
        {{-- 1. BATA SECTION --}}
        <div class="material-section">
            <div class="section-header">
                <i class="bi bi-bricks text-success"></i> Bata
            </div>
            
            <div class="form-group">
                <label>Merek</label>
                <div class="input-wrapper">
                    <select class="form-select select-green brick-brand-select" 
                            data-selected="{{ $rec ? optional($rec->brick)->brand : '' }}">
                        <option value="">-- Pilih Merk --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Dimensi</label>
                <div class="input-wrapper">
                    <select name="recommendations[{{ $index }}][brick_id]" 
                            class="form-select select-blue brick-dim-select"
                            data-selected="{{ $rec ? $rec->brick_id : '' }}">
                        <option value="">-- Pilih Dimensi --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>

            {{-- Spacer for Symmetry --}}
            <div class="form-group d-none d-lg-flex invisible">
                <label>&nbsp;</label>
                <div class="input-wrapper">
                    <select class="form-select"><option>&nbsp;</option></select>
                </div>
            </div>
        </div>

        {{-- 2. SEMEN SECTION --}}
        <div class="material-section">
            <div class="section-header">
                <i class="bi bi-box-seam text-danger"></i> Semen
            </div>

            <div class="form-group">
                <label>Jenis</label>
                <div class="input-wrapper">
                    <select class="form-select select-pink cement-type-select"
                            data-selected="{{ $rec ? optional($rec->cement)->cement_name : '' }}">
                        <option value="">-- Pilih Jenis --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Produk</label>
                <div class="input-wrapper">
                    <select name="recommendations[{{ $index }}][cement_id]" 
                            class="form-select select-orange cement-brand-select"
                            data-selected="{{ $rec ? $rec->cement_id : '' }}">
                        <option value="">-- Pilih Produk --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>

            {{-- Spacer for Symmetry --}}
            <div class="form-group d-none d-lg-flex invisible">
                <label>&nbsp;</label>
                <div class="input-wrapper">
                    <select class="form-select"><option>&nbsp;</option></select>
                </div>
            </div>
        </div>

        {{-- 3. PASIR SECTION --}}
        <div class="material-section">
            <div class="section-header">
                <i class="bi bi-bucket text-secondary"></i> Pasir
            </div>

            <div class="form-group">
                <label>Jenis</label>
                <div class="input-wrapper">
                    <select class="form-select select-gray sand-type-select"
                            data-selected="{{ $rec ? optional($rec->sand)->sand_name : '' }}">
                        <option value="">-- Pilih Jenis --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Merek</label>
                <div class="input-wrapper">
                    <select class="form-select select-gray sand-brand-select"
                            data-selected="{{ $rec ? optional($rec->sand)->brand : '' }}">
                        <option value="">-- Pilih Merk --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Kemasan</label>
                <div class="input-wrapper">
                    <select name="recommendations[{{ $index }}][sand_id]" 
                            class="form-select select-gray-light sand-pkg-select"
                            data-selected="{{ $rec ? $rec->sand_id : '' }}">
                        <option value="">-- Pilih Kemasan --</option>
                        {{-- Populated by JS --}}
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
