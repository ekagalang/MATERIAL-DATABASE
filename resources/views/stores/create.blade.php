@extends('layouts.app')

@section('title', 'Tambah Toko')

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

    <form action="{{ route('stores.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
        <input type="hidden" name="place_id" id="place_id" value="{{ old('place_id') }}">
        <input type="hidden" name="formatted_address" id="formatted_address" value="{{ old('formatted_address') }}">

        <div class="form-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1100px; width: 100%; margin: 0 auto; padding: 20px;">

            <!-- Left Column: Store & Location Info -->
            <div class="left-column">
                
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Toko & Lokasi</h5>

                <!-- Nama Toko -->
                <div class="row">
                    <label>Nama Toko <span class="text-danger">*</span></label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               class="autocomplete-input"
                               required
                               autocomplete="off"
                               placeholder="contoh: TB. Abadi">
                    </div>
                </div>

                <!-- Alamat -->
                <div class="row">
                    <label>Alamat Jalan</label>
                    <div style="flex: 1; position: relative;">
                        <textarea name="address"
                                  id="address"
                                  class="autocomplete-input"
                                  rows="3"
                                  placeholder="Jl. Merpati No. 123">{{ old('address') }}</textarea>
                        <small class="text-muted d-block mt-1">Alamat untuk lokasi pertama (opsional)</small>
                    </div>
                </div>

                <div class="row">
                    <label>Cari Lokasi</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               id="storeLocationSearch"
                               class="autocomplete-input"
                               data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                               placeholder="Cari alamat toko di Google Maps..."
                               value="{{ old('formatted_address', old('address')) }}">
                        <small class="text-muted d-block mt-1">Pilih hasil alamat, klik peta, atau geser pin untuk menentukan titik lokasi.</small>
                    </div>
                </div>

                <div class="row">
                    <label>Peta Lokasi</label>
                    <div style="flex: 1;">
                        <div id="storeLocationMap"
                             data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                             style="width: 100%; height: 260px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc;"></div>
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
                    <label>Kota/Kabupaten</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="city"
                               id="city"
                               value="{{ old('city') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Tangerang Selatan">
                    </div>
                </div>

                <!-- Provinsi -->
                <div class="row">
                    <label>Provinsi</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="province"
                               id="province"
                               value="{{ old('province') }}"
                               class="autocomplete-input"
                               placeholder="contoh: Banten">
                    </div>
                </div>

                <div class="row">
                    <label>Radius Layanan (KM)</label>
                    <div style="flex: 1; position: relative;">
                        <input type="number"
                               name="service_radius_km"
                               id="service_radius_km"
                               min="0"
                               step="0.1"
                               value="{{ old('service_radius_km', 10) }}"
                               class="autocomplete-input"
                               placeholder="contoh: 10">
                        <small class="text-muted d-block mt-1">Bebas tentukan radius layanan toko dari frontend.</small>
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
                               placeholder="contoh: Budi">
                    </div>
                </div>

                <!-- No Telepon -->
                <div class="row">
                    <label>No. Telepon</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="contact_phone"
                               id="contact_phone"
                               value="{{ old('contact_phone') }}"
                               class="autocomplete-input"
                               placeholder="08123456789">
                    </div>
                </div>

                <!-- Spacer -->
                <div style="flex-grow: 1;"></div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <a href="#" onclick="if(typeof closeFloatingModal==='function')closeFloatingModal(); return false;" class="btn-cancel" style="text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save me-1"></i> Simpan Toko
                    </button>
                </div>

            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/store-form.js') }}?v={{ @filemtime(public_path('js/store-form.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof initStoreForm === 'function') {
            initStoreForm(document);
        }
    });
</script>
@endpush
