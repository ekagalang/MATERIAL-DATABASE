@extends('layouts.app')

@section('title', 'Edit Satuan')

@section('content')
<div class="card">
    <h2>Edit Satuan</h2>

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

    <form action="{{ route('units.update', $unit->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label>Kode Satuan *</label>
                <input type="text" name="code" value="{{ old('code', $unit->code) }}" placeholder="Contoh: Kg, L, Galon" required>
            </div>

            <div class="form-group">
                <label>Nama Satuan *</label>
                <input type="text" name="name" value="{{ old('name', $unit->name) }}" placeholder="Contoh: Kilogram, Liter" required>
            </div>
        </div>

        <div class="form-group">
            <label>Berat Kemasan (Kg) *</label>
            <input type="number" name="package_weight" value="{{ old('package_weight', $unit->package_weight) }}" step="0.01" min="0" placeholder="0.00" required>
            <small style="color: #7f8c8d; display: block; margin-top: 5px;">Masukkan 0 jika satuan tidak memiliki berat kemasan</small>
        </div>

        <div class="form-group">
            <label>Keterangan</label>
            <textarea name="description" placeholder="Keterangan tambahan (opsional)">{{ old('description', $unit->description) }}</textarea>
        </div>

        <div class="btn-group btn-group-right">
            <a href="{{ route('units.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-success">Update</button>
        </div>
    </form>
</div>
@endsection