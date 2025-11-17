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

    <form action="{{ route('units.store') }}" method="POST">
        @csrf

        <!-- Kode Satuan -->
        <div class="row">
            <label>Kode Satuan</label>
            <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: Kg, L, Galon" required style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
        </div>

        <!-- Nama Satuan -->
        <div class="row">
            <label>Nama Satuan</label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Kilogram, Liter" required style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px;">
        </div>

        <!-- Berat Kemasan -->
        <div class="row">
            <label>Berat Kemasan (Kg)</label>
            <div style="flex: 1;">
                <input type="number" name="package_weight" value="{{ old('package_weight', '0') }}" step="0.01" min="0" placeholder="0.00" required style="width: 100%; padding: 7px; border: 1px solid #999; border-radius: 2px;">
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">Masukkan 0 jika satuan tidak memiliki berat kemasan</small>
            </div>
        </div>

        <!-- Keterangan -->
        <div class="row" style="align-items: flex-start;">
            <label style="padding-top: 10px;">Keterangan</label>
            <textarea name="description" placeholder="Keterangan tambahan (opsional)" style="flex: 1; padding: 7px; border: 1px solid #999; border-radius: 2px; min-height: 80px; resize: vertical;">{{ old('description') }}</textarea>
        </div>

        <!-- Buttons -->
        <div class="btnArea" style="text-align: right; margin-top: 25px;">
            <button type="button" class="btn red" onclick="window.parent.document.getElementById('closeModal').click()" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c;">Batalkan</button>
            <button type="submit" class="btn green" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: #76b245; color: #fff;">Simpan</button>
        </div>
    </form>
</div>

<style>
    .row { display: flex; margin-bottom: 15px; align-items: center; }
    label { width: 160px; padding-top: 4px; font-size: 14px; font-weight: 600; }
</style>

