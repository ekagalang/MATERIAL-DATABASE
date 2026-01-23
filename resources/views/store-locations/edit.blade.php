@extends('layouts.app')

@section('title', 'Edit Lokasi - ' . $store->name)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="mb-1">Edit Lokasi</h2>
                <p class="text-muted">Toko: <strong>{{ $store->name }}</strong></p>
                
                <!-- Incomplete Warning -->
                @if($location->is_incomplete)
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Data Belum Lengkap!</strong> Mohon lengkapi data yang ditandai dengan <span class="text-warning">*</span>
                    </div>
                @endif
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> Ada beberapa masalah:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('store-locations.update', [$store, $location]) }}" method="POST">
                @csrf
                @method('PUT')

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
                                      placeholder="Jl. Merpati No. 123, RT 001/RW 002">{{ old('address', $location->address) }}</textarea>
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
                                       value="{{ old('district', $location->district) }}" 
                                       placeholder="contoh: Ciputat">
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- City -->
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">
                                    Kota/Kabupaten 
                                    @if(empty($location->city))
                                        <span class="text-warning">* (Belum diisi)</span>
                                    @endif
                                </label>
                                <input type="text" 
                                       class="form-control @error('city') is-invalid @enderror @if(empty($location->city)) border-warning @endif" 
                                       id="city" 
                                       name="city" 
                                       value="{{ old('city', $location->city) }}" 
                                       placeholder="contoh: Tangerang Selatan">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Province -->
                        <div class="mb-3">
                            <label for="province" class="form-label">
                                Provinsi
                                @if(empty($location->province))
                                    <span class="text-warning">* (Belum diisi)</span>
                                @endif
                            </label>
                            <input type="text" 
                                   class="form-control @error('province') is-invalid @enderror @if(empty($location->province)) border-warning @endif" 
                                   id="province" 
                                   name="province" 
                                   value="{{ old('province', $location->province) }}" 
                                   placeholder="contoh: Banten">
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                       value="{{ old('contact_name', $location->contact_name) }}" 
                                       placeholder="contoh: Budi Santoso">
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contact Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">
                                    No. Telepon
                                    @if(empty($location->contact_phone))
                                        <span class="text-warning">* (Belum diisi)</span>
                                    @endif
                                </label>
                                <input type="text" 
                                       class="form-control @error('contact_phone') is-invalid @enderror @if(empty($location->contact_phone)) border-warning @endif" 
                                       id="contact_phone" 
                                       name="contact_phone" 
                                       value="{{ old('contact_phone', $location->contact_phone) }}" 
                                       placeholder="08123456789">
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if($location->is_incomplete)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Info:</strong> Lengkapi field yang ditandai dengan <span class="text-warning">*</span> agar data menjadi lengkap
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('stores.show', $store) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection