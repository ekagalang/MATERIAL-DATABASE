@extends('layouts.app')

@section('title', 'Tambah Satuan')

@section('content')
<div class="card">
    <h2>Tambah Satuan Baru</h2>

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

        <div class="form-row">
            <div class="form-group">
                <label>Kode Satuan *</label>
                <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: Kg, L, Galon" required>
            </div>

            <div class="form-group">
                <label>Nama Satuan *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Kilogram, Liter" required>
            </div>
        </div>

        <div class="form-group">
            <label>Berat Kemasan (Kg) *</label>
            <input type="number" name="package_weight" value="{{ old('package_weight', '0') }}" step="0.01" min="0" placeholder="0.00" required>
            <small style="color: #7f8c8d; display: block; margin-top: 5px;">Masukkan 0 jika satuan tidak memiliki berat kemasan</small>
        </div>

        <div class="form-group">
            <label>Keterangan</label>
            <textarea name="description" placeholder="Keterangan tambahan (opsional)">{{ old('description') }}</textarea>
        </div>

        <div class="btn-group btn-group-right">
            <a href="{{ route('units.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-success">Simpan</button>
        </div>
    </form>
</div>
@endsection

