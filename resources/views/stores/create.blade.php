@extends('layouts.app')

@section('title', 'Tambah Toko')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="mb-1">Tambah Toko Baru</h2>
                <p class="text-muted">Isi informasi toko dan lokasi pertama</p>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> Ada beberapa masalah dengan input Anda:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('stores.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Toko</h5>
                    </div>
                    <div class="card-body">
                        <!-- Store Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Toko <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="contoh: TB. Abadi">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Logo Removed -->
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Lokasi Pertama (Opsional)</h5>
                        <small class="text-muted">Anda bisa menambahkan lokasi setelah toko dibuat</small>
                    </div>
                    <div class="card-body">
                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat Jalan</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="2" 
                                      placeholder="Jl. Merpati No. 123">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- District -->
                            <div class="col-md-6 mb-3">
                                <label for="district" class="form-label">Kecamatan</label>
                                <input type="text" 
                                       class="form-control @error('district') is-invalid @enderror" 
                                       id="district" 
                                       name="district" 
                                       value="{{ old('district') }}" 
                                       placeholder="contoh: Ciputat">
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- City -->
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Kota/Kabupaten</label>
                                <input type="text" 
                                       class="form-control @error('city') is-invalid @enderror" 
                                       id="city" 
                                       name="city" 
                                       value="{{ old('city') }}" 
                                       placeholder="contoh: Tangerang Selatan">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Province -->
                        <div class="mb-3">
                            <label for="province" class="form-label">Provinsi</label>
                            <input type="text" 
                                   class="form-control @error('province') is-invalid @enderror" 
                                   id="province" 
                                   name="province" 
                                   value="{{ old('province') }}" 
                                   placeholder="contoh: Banten">
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Contact Name -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Nama Kontak (PIC)</label>
                                <input type="text" 
                                       class="form-control @error('contact_name') is-invalid @enderror" 
                                       id="contact_name" 
                                       name="contact_name" 
                                       value="{{ old('contact_name') }}" 
                                       placeholder="contoh: Budi">
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">No. Telepon</label>
                                <input type="text" 
                                       class="form-control @error('contact_phone') is-invalid @enderror" 
                                       id="contact_phone" 
                                       name="contact_phone" 
                                       value="{{ old('contact_phone') }}" 
                                       placeholder="08123456789">
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('stores.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Toko
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection