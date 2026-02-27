@extends('layouts.app')

@section('title', 'Pengaturan Radius Pencarian Toko')

@section('content')
    <h2 class="mb-3">Pengaturan Radius Pencarian Toko</h2>

    <div class="card mb-3" style="border: 1px solid #e2e8f0; border-radius: 12px;">
        <div class="card-body" style="padding: 16px;">
            <form action="{{ route('settings.store-search-radius.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="project_store_radius_default_km" style="font-weight: 600;">
                            Radius Proyek Default (km)
                        </label>
                        <input type="number"
                               step="0.1"
                               min="0.1"
                               max="1000"
                               class="form-control"
                               id="project_store_radius_default_km"
                               name="project_store_radius_default_km"
                               value="{{ old('project_store_radius_default_km', $projectStoreRadiusDefaultKm ?? 10) }}"
                               required>
                        <small class="text-muted">
                            Radius utama proyek untuk mode <b>Lengkap &gt; Dalam Radius</b>. Default: 10 km.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="project_store_radius_final_km" style="font-weight: 600;">
                            Radius Ke-2 / Batas Akhir (km)
                        </label>
                        <input type="number"
                               step="0.1"
                               min="0.1"
                               max="1000"
                               class="form-control"
                               id="project_store_radius_final_km"
                               name="project_store_radius_final_km"
                               value="{{ old('project_store_radius_final_km', $projectStoreRadiusFinalKm ?? 15) }}"
                               required>
                        <small class="text-muted">
                            Batas maksimum pencarian toko yang wajib dipatuhi untuk semua mode pencarian toko.
                        </small>
                    </div>
                </div>

                <div class="mt-3 p-3" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
                    <div style="font-weight: 700; color: #334155; margin-bottom: 6px;">Aturan Pencarian</div>
                    <div class="text-muted" style="font-size: 13px; line-height: 1.55;">
                        Sistem akan selalu mengurutkan toko dari jarak terdekat ke lokasi proyek.
                        Jika tidak ada toko di radius utama, pencarian dapat diperluas hingga Radius Ke-2 (batas akhir).
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary-glossy">
                        <i class="bi bi-save me-1"></i> Simpan Setting Radius
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
