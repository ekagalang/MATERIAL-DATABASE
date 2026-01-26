@extends('layouts.app')

@section('title', 'Edit Toko - ' . $store->name)

@section('content')
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

    <form action="{{ route('stores.update', $store) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-container" style="display: grid; grid-template-columns: 1fr; gap: 30px; max-width: 800px; width: 100%; margin: 0 auto; padding: 20px;">

            <!-- Kolom Kiri - Form Fields -->
            <div class="left-column">
                
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Toko</h5>

                <!-- Nama Toko -->
                <div class="row">
                    <label>Nama Toko <span class="text-danger">*</span></label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name', $store->name) }}"
                               class="autocomplete-input"
                               required
                               autocomplete="off">
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <a href="#" onclick="if(typeof closeFloatingModal==='function')closeFloatingModal(); return false;" class="btn-cancel" style="text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save me-1"></i> Update Toko
                    </button>
                </div>

            </div>
        </div>

    </form>
</div>
@endsection