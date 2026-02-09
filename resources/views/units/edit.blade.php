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

    <form action="{{ route('units.update', $unit->id) }}" method="POST" id="unitForm">
        @csrf
        @method('PUT')

        <div style="max-width: 680px;">
            <!-- Material Type -->
            <div class="row">
                <label>Material Type <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff;">
                        @foreach($materialTypes as $type => $label)
                            <div class="form-check" style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" 
                                       name="material_types[]" 
                                       id="mt_{{ $type }}" 
                                       value="{{ $type }}" 
                                       style="width: 16px; height: 16px; cursor: pointer; accent-color: #891313;"
                                       {{ in_array($type, old('material_types', $selectedTypes ?? [])) ? 'checked' : '' }}>
                                <label for="mt_{{ $type }}" style="width: auto; padding: 0; margin: 0; font-weight: 500; cursor: pointer; color: #475569; font-size: 13.5px;">
                                    {{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 6px;">
                        Pilih satu atau lebih material yang menggunakan satuan ini.
                    </small>
                    @error('material_types')
                        <small style="color: #ef4444; font-size: 12px; display: block; margin-top: 6px; font-weight: 500;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>

            <!-- Code -->
            <div class="row">
                <label>Kode Satuan <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" 
                           name="code" 
                           id="code" 
                           value="{{ old('code', $unit->code) }}" 
                           required 
                           placeholder="Contoh: Kg, Galon, Sak"
                           style="width: 100%; 
                                  padding: 10px 14px; 
                                  border: 1.5px solid #e2e8f0; 
                                  border-radius: 10px; 
                                  font-size: 13.5px;
                                  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    @error('code')
                        <small style="color: #ef4444; font-size: 12px; display: block; margin-top: 6px; font-weight: 500;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>

            <!-- Name -->
            <div class="row">
                <label>Nama Satuan <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $unit->name) }}" 
                           required 
                           placeholder="Contoh: Kilogram, Galon, Sak"
                           style="width: 100%; 
                                  padding: 10px 14px; 
                                  border: 1.5px solid #e2e8f0; 
                                  border-radius: 10px; 
                                  font-size: 13.5px;
                                  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    @error('name')
                        <small style="color: #ef4444; font-size: 12px; display: block; margin-top: 6px; font-weight: 500;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>

            <!-- Package Weight -->
            <div class="row">
                <label>Berat Kemasan <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" inputmode="decimal" 
                               name="package_weight" 
                               id="package_weight" 
                               value="{{ old('package_weight', $unit->package_weight) }}" 
                               step="0.01" 
                               min="0" 
                               required 
                               placeholder="0"
                               style="flex: 1; 
                                      max-width: 200px;
                                      padding: 10px 14px; 
                                      border: 1.5px solid #e2e8f0; 
                                      border-radius: 10px; 
                                      font-size: 13.5px;
                                      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">Kg</span>
                    </div>
                    <small style="color: #94a3b8; font-size: 11.5px; display: block; margin-top: 6px;">
                        Berat kemasan kosong dalam Kg (isi 0 jika tidak ada kemasan)
                    </small>
                    @error('package_weight')
                        <small style="color: #ef4444; font-size: 12px; display: block; margin-top: 6px; font-weight: 500;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="row">
                <label>Keterangan</label>
                <div style="flex: 1;">
                    <textarea name="description" 
                              id="description" 
                              rows="3" 
                              placeholder="Keterangan tambahan (opsional)" 
                              style="width: 100%; 
                                     padding: 10px 14px; 
                                     border: 1.5px solid #e2e8f0; 
                                     border-radius: 10px; 
                                     font-size: 13.5px; 
                                     resize: vertical; 
                                     min-height: 90px;
                                     font-family: inherit;
                                     transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">{{ old('description', $unit->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn-cancel"
                    onclick="if(typeof window.closeFloatingModalLocal === 'function'){ window.closeFloatingModalLocal(); } else if(typeof window.closeFloatingModal === 'function'){ window.closeFloatingModal(); }">Batal</button>
            <button type="submit" class="btn-save">Update</button>
        </div>

    </form>
</div>

<style>
    .row {
        display: flex;
        margin-bottom: 18px;
        align-items: flex-start;
        gap: 16px;
    }

    label {
        width: 180px;
        padding-top: 10px;
        font-size: 13.5px;
        font-weight: 600;
        color: inherit;
        flex-shrink: 0;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #891313 !important;
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08) !important;
        background: #fffbfb !important;
    }

    input::placeholder, textarea::placeholder {
        color: #94a3b8;
    }
</style>
