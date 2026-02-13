@extends('layouts.app')

@section('title', 'Edit Lokasi - ' . $store->name)

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

    <form action="{{ route('store-locations.update', [$store, $location]) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $location->latitude) }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $location->longitude) }}">
        <input type="hidden" name="place_id" id="place_id" value="{{ old('place_id', $location->place_id) }}">
        <input type="hidden" name="formatted_address" id="formatted_address" value="{{ old('formatted_address', $location->formatted_address) }}">

        <div class="form-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1100px; width: 100%; margin: 0 auto; padding: 20px;">

            <!-- Left Column: Location Info -->
            <div class="left-column">
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Lokasi</h5>
                <p class="text-muted small mb-4">Toko: <strong>{{ $store->name }}</strong></p>

                <!-- Incomplete Warning -->
                @if($location->is_incomplete)
                    <div class="alert alert-warning py-2 px-3 small mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <div><strong>Data Belum Lengkap!</strong> Mohon lengkapi data yang ditandai dengan <span class="text-warning">*</span></div>
                    </div>
                @endif

                <!-- Alamat -->
                <div class="row">
                    <label>Alamat Jalan</label>
                    <div style="flex: 1; position: relative;">
                        <textarea name="address"
                                  id="address"
                                  class="autocomplete-input"
                                  rows="3"
                                  placeholder="Jl. Merpati No. 123, RT 001/RW 002">{{ old('address', $location->address) }}</textarea>
                    </div>
                </div>

                <div class="row">
                    <label>Cari Lokasi</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               id="storeLocationSearch"
                               class="autocomplete-input"
                               data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                               placeholder="Cari alamat lokasi toko di Google Maps..."
                               value="{{ old('formatted_address', $location->formatted_address ?: $location->address) }}">
                        <small class="text-muted d-block mt-1">Pilih alamat dari Google Maps atau sesuaikan pin pada peta.</small>
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
                               value="{{ old('district', $location->district) }}"
                               class="autocomplete-input"
                               placeholder="contoh: Ciputat">
                    </div>
                </div>

                <!-- Kota/Kabupaten -->
                <div class="row">
                    <label>
                        Kota/Kabupaten 
                        @if(empty($location->city))
                            <span class="text-warning">*</span>
                        @endif
                    </label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="city"
                               id="city"
                               value="{{ old('city', $location->city) }}"
                               class="autocomplete-input @if(empty($location->city)) border-warning @endif"
                               placeholder="contoh: Tangerang Selatan">
                    </div>
                </div>

                <!-- Provinsi -->
                <div class="row">
                    <label>
                        Provinsi
                        @if(empty($location->province))
                            <span class="text-warning">*</span>
                        @endif
                    </label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="province"
                               id="province"
                               value="{{ old('province', $location->province) }}"
                               class="autocomplete-input @if(empty($location->province)) border-warning @endif"
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
                               value="{{ old('service_radius_km', $location->service_radius_km ?? 10) }}"
                               class="autocomplete-input"
                               placeholder="contoh: 10">
                        <small class="text-muted d-block mt-1">Bebas tentukan radius layanan lokasi ini.</small>
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
                               value="{{ old('contact_name', $location->contact_name) }}"
                               class="autocomplete-input"
                               placeholder="contoh: Budi Santoso">
                    </div>
                </div>

                <!-- No Telepon -->
                <div class="row">
                    <label>
                        No. Telepon
                        @if(empty($location->contact_phone))
                            <span class="text-warning">*</span>
                        @endif
                    </label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="contact_phone"
                               id="contact_phone"
                               value="{{ old('contact_phone', $location->contact_phone) }}"
                               class="autocomplete-input @if(empty($location->contact_phone)) border-warning @endif"
                               placeholder="08123456789">
                    </div>
                </div>

                @if($location->is_incomplete)
                    <div class="alert alert-info mt-3 mb-0 py-2 px-3 small">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Info:</strong> Lengkapi field yang ditandai dengan * agar data menjadi lengkap
                    </div>
                @endif

                <!-- Spacer -->
                <div style="flex-grow: 1;"></div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <a href="#" onclick="if(typeof closeFloatingModal==='function')closeFloatingModal(); return false;" class="btn-cancel" style="text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save me-1"></i> Update Lokasi
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/store-location-form.js') }}?v={{ @filemtime(public_path('js/store-location-form.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof initStoreLocationForm === 'function') {
            initStoreLocationForm(document);
        }
    });
</script>
@endpush
