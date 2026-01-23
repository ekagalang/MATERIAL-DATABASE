@extends('layouts.app')

@section('title', 'Tambah Lokasi - ' . $store->name)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="mb-1">Tambah Lokasi Baru</h2>
                <p class="text-muted">Untuk toko: <strong>{{ $store->name }}</strong></p>
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

            <form action="{{ route('store-locations.store', $store) }}" method="POST">
                @csrf

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Lokasi</h5>
                    </div>
                    <div class="card-body">
                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">
                                Alamat Jalan
                            </label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="3" 
                                      placeholder="Jl. Merpati No. 123, RT 001/RW 002">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Alamat lengkap termasuk nomor dan RT/RW</small>
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
                                <label for="city" class="form-label">
                                    Kota/Kabupaten <span class="text-warning">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('city') is-invalid @enderror" 
                                       id="city" 
                                       name="city" 
                                       value="{{ old('city') }}" 
                                       placeholder="contoh: Tangerang Selatan">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Dibutuhkan untuk data lengkap</small>
                            </div>
                        </div>

                        <!-- Province -->
                        <div class="mb-3">
                            <label for="province" class="form-label">
                                Provinsi <span class="text-warning">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('province') is-invalid @enderror" 
                                   id="province" 
                                   name="province" 
                                   value="{{ old('province') }}" 
                                   placeholder="contoh: Banten">
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Dibutuhkan untuk data lengkap</small>
                        </div>

                        <hr>

                        <div class="row">
                            <!-- Contact Name -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Nama Kontak (PIC)</label>
                                <input type="text" 
                                       class="form-control @error('contact_name') is-invalid @enderror" 
                                       id="contact_name" 
                                       name="contact_name" 
                                       value="{{ old('contact_name') }}" 
                                       placeholder="contoh: Budi Santoso">
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">
                                    No. Telepon <span class="text-warning">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('contact_phone') is-invalid @enderror" 
                                       id="contact_phone" 
                                       name="contact_phone" 
                                       value="{{ old('contact_phone') }}" 
                                       placeholder="08123456789">
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Dibutuhkan untuk data lengkap</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Tips:</strong> Untuk data lengkap, pastikan mengisi Kota, Provinsi, dan No. Telepon
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('stores.show', $store) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection