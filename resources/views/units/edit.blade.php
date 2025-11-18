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

    <form action="{{ route('units.update', $unit->id) }}" method="POST" id="unitForm">
        @csrf
        @method('PUT')

        <div style="max-width: 600px;">
            <!-- Material Type -->
            <div class="row">
                <label>Material Type <span style="color: red;">*</span></label>
                <div style="flex: 1;">
                    <select name="material_type" id="material_type" required style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                        <option value="">-- Pilih Material Type --</option>
                        @foreach($materialTypes as $type => $label)
                            <option value="{{ $type }}" {{ old('material_type', $unit->material_type) == $type ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('material_type')
                        <small style="color: #dc2626; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Code -->
            <div class="row">
                <label>Kode Satuan <span style="color: red;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" name="code" id="code" value="{{ old('code', $unit->code) }}" required style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                    @error('code')
                        <small style="color: #dc2626; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Name -->
            <div class="row">
                <label>Nama Satuan <span style="color: red;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" name="name" id="name" value="{{ old('name', $unit->name) }}" required style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                    @error('name')
                        <small style="color: #dc2626; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Package Weight -->
            <div class="row">
                <label>Berat Kemasan (Kg) <span style="color: red;">*</span></label>
                <div style="flex: 1;">
                    <input type="number" name="package_weight" id="package_weight" value="{{ old('package_weight', $unit->package_weight) }}" step="0.01" min="0" required style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                    <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 4px;">Berat kemasan kosong dalam Kg</small>
                    @error('package_weight')
                        <small style="color: #dc2626; font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="row">
                <label>Keterangan</label>
                <div style="flex: 1;">
                    <textarea name="description" id="description" rows="3" placeholder="Keterangan tambahan (optional)" style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px; resize: vertical; min-height: 80px;">{{ old('description', $unit->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="btnArea" style="text-align: right; margin-top: 25px;">
            <button type="button" class="btn red" onclick="window.parent.document.getElementById('closeModal').click()" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c;">Batalkan</button>
            <button type="submit" class="btn green" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: #76b245; color: #fff;">Update</button>
        </div>

    </form>
</div>

<style>
    .row { 
        display: flex; 
        margin-bottom: 15px; 
        align-items: flex-start; 
    }
    
    label { 
        width: 180px; 
        padding-top: 8px; 
        font-size: 14px; 
        font-weight: 600; 
    }
    
    input, select, textarea { 
        flex: 1; 
        padding: 7px; 
        border: 1px solid #999; 
        border-radius: 2px; 
        font-family: inherit;
        font-size: 14px;
    }
    
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #891313;
        box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1);
    }
    
    select {
        cursor: pointer;
    }
</style>