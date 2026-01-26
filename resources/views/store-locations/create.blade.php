@extends('layouts.app')

@section('title', 'Tambah Lokasi - ' . $store->name)

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

    <form action="{{ route('store-locations.store', $store) }}" method="POST">
        @csrf

        <div class="form-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1100px; width: 100%; margin: 0 auto; padding: 20px;">

            <!-- Left Column: Location Info -->
            <div class="left-column">
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Lokasi</h5>
                <p class="text-muted small mb-4">Untuk toko: <strong>{{ $store->name }}</strong></p>

                <!-- Alamat -->
                <div class="row">
                    <label>Alamat Jalan</label>
                    <div style="flex: 1; position: relative;">
                        <textarea name="address"
                                  id="address"
                                  class="autocomplete-input"
                                  rows="3"
                                  placeholder="Jl. Merpati No. 123, RT 001/RW 002">{{ old('address') }}</textarea>
                        <small class="text-muted d-block mt-1">Alamat lengkap termasuk nomor dan RT/RW</small>
                    </div>
                </div>

                <!-- Kecamatan -->
                <div class="row">
                    <label>Kecamatan</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="district"
                               id="district"
                               value="{{ old('district') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Ciputat">
                    </div>
                </div>

                <!-- Kota/Kabupaten -->
                <div class="row">
                    <label>Kota/Kabupaten <span class="text-warning">*</span></label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="city"
                               id="city"
                               value="{{ old('city') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Tangerang Selatan">
                        <small class="text-muted d-block mt-1">Dibutuhkan untuk data lengkap</small>
                    </div>
                </div>

                <!-- Provinsi -->
                <div class="row">
                    <label>Provinsi <span class="text-warning">*</span></label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="province"
                               id="province"
                               value="{{ old('province') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Banten">
                        <small class="text-muted d-block mt-1">Dibutuhkan untuk data lengkap</small>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact Info & Actions -->
            <div class="right-column" style="display: flex; flex-direction: column;">
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Kontak</h5>
                <p class="text-muted small mb-4" style="visibility: hidden;">Spacer</p>

                <!-- Nama Kontak -->
                <div class="row">
                    <label>Nama Kontak</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="contact_name"
                               id="contact_name"
                               value="{{ old('contact_name') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Budi Santoso">
                    </div>
                </div>

                <!-- No Telepon -->
                <div class="row">
                    <label>No. Telepon <span class="text-warning">*</span></label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="contact_phone"
                               id="contact_phone"
                               value="{{ old('contact_phone') }}"
                               class="autocomplete-input"
                               placeholder="08123456789">
                        <small class="text-muted d-block mt-1">Dibutuhkan untuk data lengkap</small>
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-0 py-2 px-3 small">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Tips:</strong> Untuk data lengkap, pastikan mengisi Kota, Provinsi, dan No. Telepon
                </div>

                <!-- Spacer to push buttons to bottom -->
                <div style="flex-grow: 1;"></div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <a href="#" onclick="if(typeof closeFloatingModal==='function')closeFloatingModal(); return false;" class="btn-cancel" style="text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save me-1"></i> Simpan Lokasi
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection