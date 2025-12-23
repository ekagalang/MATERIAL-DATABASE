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

    <form action="{{ route('work-items.update', $workItem->id) }}" method="POST" id="workItemForm">
        @csrf
        @method('PUT')

        <div style="max-width: 680px;">
            <!-- Name -->
            <div class="row">
                <label>Nama Item <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $workItem->name) }}" 
                           required 
                           placeholder="Contoh: Pasang Bata Merah 1:4" 
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                </div>
            </div>

            <!-- Category -->
            <div class="row">
                <label>Kategori</label>
                <div style="flex: 1;">
                    <input type="text" 
                           name="category" 
                           id="category" 
                           value="{{ old('category', $workItem->category) }}" 
                           placeholder="Contoh: Pekerjaan Dinding" 
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                </div>
            </div>

            <!-- Unit -->
            <div class="row">
                <label>Satuan <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <input type="text" 
                           name="unit" 
                           id="unit" 
                           value="{{ old('unit', $workItem->unit) }}" 
                           required 
                           placeholder="Contoh: m2, m3, unit" 
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                </div>
            </div>

            <!-- Price -->
            <div class="row">
                <label>Harga Satuan <span style="color: #ef4444;">*</span></label>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; position: relative;">
                        <span style="position: absolute; left: 14px; font-weight: 600; color: #64748b;">Rp</span>
                        <input type="number" 
                               name="price" 
                               id="price" 
                               value="{{ old('price', $workItem->price) }}" 
                               min="0" 
                               step="0.01"
                               required 
                               placeholder="0" 
                               style="width: 100%; padding: 10px 14px 10px 45px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px;">
                    </div>
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
                              style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; resize: vertical;">{{ old('description', $workItem->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
            <button type="button" 
                    class="btn btn-secondary" 
                    onclick="if(document.getElementById('closeModal')){ document.getElementById('closeModal').click(); } else { window.history.back(); }">
                <i class="bi bi-x-lg"></i> Batal
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Update Data
            </button>
        </div>
    </form>
</div>
