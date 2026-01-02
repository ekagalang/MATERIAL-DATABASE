<div class="card" style="box-shadow: none; border: none; background: transparent;">
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('ceramics.store') }}" method="POST" enctype="multipart/form-data" id="ceramicForm">
        @csrf

        <div class="form-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; width: 100%;">

            <div class="left-column">
                
                <div class="row" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Merek</label>
                    <div style="position: relative;">
                        <input type="text" name="brand" value="{{ old('brand') }}" class="autocomplete-input" data-field="brand" placeholder="Contoh: Roman, Mulia..." required 
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: all 0.2s;">
                    </div>
                </div>

                <div class="row" style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Sub Merek / Seri</label>
                        <input type="text" name="sub_brand" value="{{ old('sub_brand') }}" 
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Kode Produk</label>
                        <input type="text" name="code" value="{{ old('code') }}" 
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                    </div>
                </div>

                <div class="row" style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Jenis</label>
                        <select name="type" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white;">
                            <option value="Lantai">Lantai</option>
                            <option value="Dinding">Dinding</option>
                            <option value="Granit">Granit</option>
                            <option value="Teras">Teras/Outdoor</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Warna / Motif</label>
                        <input type="text" name="color" value="{{ old('color') }}" 
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                    </div>
                </div>

                <div class="row" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Bentuk</label>
                    <select name="form" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white;">
                        <option value="Persegi">Persegi</option>
                        <option value="Persegi Panjang">Persegi Panjang</option>
                        <option value="Hexagon">Hexagon</option>
                    </select>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
                    <div class="row" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Toko</label>
                        <div style="position: relative;">
                            <input type="text" name="store" value="{{ old('store') }}" class="autocomplete-input" data-field="store" 
                                   style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                        </div>
                    </div>
                    <div class="row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Alamat</label>
                        <textarea name="address" rows="2" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="right-column">
                
                <div class="row" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Dimensi (cm)</label>
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <div style="flex: 1;">
                            <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; font-style: italic;">Panjang</span>
                            <input type="number" step="0.1" name="dimension_length" id="dimension_length" value="{{ old('dimension_length') }}" required 
                                   style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                        </div>
                        <div style="padding-bottom: 12px; color: #cbd5e1;">x</div>
                        <div style="flex: 1;">
                            <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; font-style: italic;">Lebar</span>
                            <input type="number" step="0.1" name="dimension_width" id="dimension_width" value="{{ old('dimension_width') }}" required 
                                   style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                        </div>
                    </div>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; font-style: italic;">Tebal (mm)</span>
                        <input type="number" step="0.1" name="dimension_thickness" value="{{ old('dimension_thickness') }}" 
                               style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #f1f5f9; margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <div style="flex: 1;">
                            <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Kemasan</label>
                            <input type="text" name="packaging" value="Dus" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Isi (Pcs)</label>
                            <input type="number" name="pieces_per_package" id="pieces_per_package" value="{{ old('pieces_per_package') }}" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Luas per Dus (m²)</label>
                        <input type="number" step="0.0001" name="coverage_per_package" id="coverage_per_package" value="{{ old('coverage_per_package') }}" placeholder="Auto" 
                               style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #f1f5f9;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #334155;">Harga per Dus</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; font-weight: 500; font-size: 14px;">Rp</span>
                        <input type="number" name="price_per_package" id="price_per_package" value="{{ old('price_per_package') }}" required 
                               style="width: 100%; padding: 12px 14px 12px 40px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-weight: 600; color: #0f172a; font-size: 15px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Estimasi per m²</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;">Rp</span>
                        <input type="text" id="comparison_price_per_m2" readonly 
                               style="width: 100%; padding: 10px 14px 10px 40px; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px;">
                    </div>
                </div>

                <div class="image-upload-container" style="border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; position: relative; background: #fafbfc; transition: all 0.2s; cursor: pointer;" onclick="document.getElementById('photo').click()">
                    <div id="imagePreview" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px;">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 32px; color: #94a3b8; margin-bottom: 8px;"></i>
                        <span style="font-size: 13px; color: #64748b;">Klik untuk upload foto</span>
                        <img id="preview-img" src="" alt="Preview" style="display: none; max-width: 100%; max-height: 150px; border-radius: 8px; margin-top: 10px; object-fit: contain;">
                    </div>
                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                </div>

                <div style="display: flex; justify-content: center; gap: 20px; margin-top: 30px; padding-bottom: 10px;">
                    <button type="button" class="btn-cancel" onclick="closeFloatingModal()" 
                            style="padding: 12px 30px; border-radius: 10px; font-weight: 500; color: #64748b; background: white; border: 1px solid #e2e8f0; cursor: pointer;">Batal</button>
                    <button type="submit" class="btn-save" 
                            style="padding: 12px 40px; border-radius: 10px; font-weight: 600; color: white; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: none; box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2); cursor: pointer;">Simpan</button>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="{{ asset('js/ceramic-form.js') }}"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('preview-img').style.display = 'block';
            // Hide icon
            input.parentElement.querySelector('i').style.display = 'none';
            input.parentElement.querySelector('span').style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>