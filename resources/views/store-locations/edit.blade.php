@extends('layouts.app')

@section('title', 'Edit Lokasi - ' . $store->name)

@section('content')
<div class="card store-location-modal">
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

    <style>
        .store-location-modal .row {
            gap: 0 !important;
        }

        .store-location-modal .row > label {
            width: 118px !important;
            min-width: 118px !important;
            flex: 0 0 118px !important;
            margin-right: 0 !important;
            padding-top: 6px !important;
        }

        .store-location-modal .store-contact-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .store-location-modal .store-contact-row .autocomplete-input {
            flex: 1 1 0;
            min-width: 0;
        }

        .store-location-modal .store-contact-remove-btn {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            line-height: 1;
        }

        .store-location-modal .store-contact-remove-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .store-location-modal .store-contact-add-btn {
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        @media (max-width: 992px) {
            .store-location-modal .store-location-form-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
        }
    </style>

    @php
        $splitContactValues = static function (?string $value): array {
            $text = trim((string) $value);
            if ($text === '') {
                return [''];
            }

            $parts = array_map(static fn($item) => trim($item), explode('|', $text));
            $parts = array_values(array_filter($parts, static fn($item) => $item !== ''));

            return !empty($parts) ? $parts : [''];
        };

        $defaultContactNames = $splitContactValues($location->contact_name);
        $defaultContactPhones = $splitContactValues($location->contact_phone);

        $oldContactNames = old('contact_name', $defaultContactNames);
        if (!is_array($oldContactNames)) {
            $oldContactNames = [$oldContactNames];
        }

        $oldContactPhones = old('contact_phone', $defaultContactPhones);
        if (!is_array($oldContactPhones)) {
            $oldContactPhones = [$oldContactPhones];
        }

        $contactRowCount = max(count($oldContactNames), count($oldContactPhones), 1);
        $highlightMissingPhone = empty(trim((string) $location->contact_phone)) && !old('contact_phone');
    @endphp

    <form action="{{ route('store-locations.update', [$store, $location]) }}" method="POST" class="store-location-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $location->latitude) }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $location->longitude) }}">
        <input type="hidden" name="place_id" id="place_id" value="{{ old('place_id', $location->place_id) }}">
        <input type="hidden" name="formatted_address" id="formatted_address" value="{{ old('formatted_address', $location->formatted_address) }}">

        <div class="form-container store-location-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1100px; width: 100%; margin: 0 auto; padding: 20px;">

            <!-- Left Column: Location Info -->
            <div class="left-column">
                <h5 class="mb-3 text-secondary border-bottom pb-2">Informasi Lokasi</h5>

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

                <!-- Kecamatan -->
                <div class="row">
                    <label>Kecamatan</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text"
                               name="district"
                               id="district"
                               value="{{ old('district', $location->district) }}"
                               class="autocomplete-input"
                               placeholder="contoh: Serpong">
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

                <h5 class="mt-4 mb-3 text-secondary border-bottom pb-2">Informasi Kontak</h5>

                <div class="row">
                    <label>
                        Kontak
                        @if ($highlightMissingPhone)
                            <span class="text-warning">*</span>
                        @endif
                    </label>
                    <div style="flex: 1; position: relative;">
                        <div class="d-flex flex-column gap-2" data-contact-list>
                            @for ($i = 0; $i < $contactRowCount; $i++)
                                <div class="store-contact-row" data-contact-row>
                                    <input type="text"
                                           name="contact_name[]"
                                           value="{{ $oldContactNames[$i] ?? '' }}"
                                           class="autocomplete-input"
                                           placeholder="Nama kontak">
                                    <input type="text"
                                           name="contact_phone[]"
                                           value="{{ $oldContactPhones[$i] ?? '' }}"
                                           class="autocomplete-input {{ $highlightMissingPhone && $i === 0 ? 'border-warning' : '' }}"
                                           inputmode="tel"
                                           pattern="[0-9+\- ]*"
                                           placeholder="No. telepon">
                                    <button type="button" class="store-contact-remove-btn" data-contact-remove aria-label="Hapus kontak">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            @endfor
                        </div>
                        <button type="button" class="store-contact-add-btn mt-2" data-contact-add>
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kontak
                        </button>
                    </div>
                </div>

                @if($location->is_incomplete)
                    <div class="alert alert-info mt-3 mb-0 py-2 px-3 small">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Info:</strong> Lengkapi field yang ditandai dengan * agar data menjadi lengkap
                    </div>
                @endif
            </div>

            <!-- Right Column: Search Map & Actions -->
            <div class="right-column" style="display: flex; flex-direction: column;">
                <h5 class="mb-3 text-secondary border-bottom pb-2">Lokasi Map</h5>

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
                    <div style="flex: 1;">
                        <div id="storeLocationMap"
                             data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                             style="width: 100%; height: 260px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc;"></div>
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
                        <i class="bi bi-save me-1"></i> Update Lokasi
                    </button>
                </div>
            </div>
        </div>

        <template id="store-location-contact-row-template-edit" data-contact-template>
            <div class="store-contact-row" data-contact-row>
                <input type="text"
                       name="contact_name[]"
                       value=""
                       class="autocomplete-input"
                       placeholder="Nama kontak">
        <input type="text"
               name="contact_phone[]"
               value=""
               class="autocomplete-input"
               inputmode="tel"
               pattern="[0-9+\- ]*"
               placeholder="No. telepon">
                <button type="button" class="store-contact-remove-btn" data-contact-remove aria-label="Hapus kontak">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </template>
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
