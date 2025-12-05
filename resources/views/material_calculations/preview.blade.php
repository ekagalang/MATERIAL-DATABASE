@extends('layouts.app')

@section('content')
<div class="card">
    <h3 class="form-title"><i class="bi bi-eye text-primary"></i> Preview Hasil Perhitungan</h3>
    <p class="text-muted" style="margin-top:-6px;">
        Silakan periksa hasil perhitungan di bawah ini. Jika sudah sesuai, simpan sebagai riwayat perhitungan.
    </p>

    <div class="mb-3">
        <h5>Ringkasan Dinding</h5>
        <ul class="mb-0">
            <li>Panjang: {{ $summary['wall_info']['length'] }}</li>
            <li>Tinggi: {{ $summary['wall_info']['height'] }}</li>
            <li>Luas: {{ $summary['wall_info']['area'] }}</li>
        </ul>
    </div>

    <div class="mb-3">
        <h5>Ringkasan Bata & Adukan</h5>
        <ul class="mb-0">
            <li>Jumlah Bata: {{ $summary['brick_info']['quantity'] }}</li>
            <li>Jenis Pemasangan: {{ $summary['brick_info']['type'] }}</li>
            <li>Formula Adukan: {{ $summary['mortar_info']['formula'] }}</li>
            <li>Tebal Adukan: {{ $summary['mortar_info']['thickness'] }}</li>
        </ul>
    </div>

    <div class="mb-3">
        <h5>Material</h5>
        <ul class="mb-0">
            <li>Semen: {{ $summary['materials']['cement']['kg'] }} ({{ $summary['materials']['cement']['quantity_sak'] }})</li>
            <li>Pasir: {{ $summary['materials']['sand']['m3'] }} ({{ $summary['materials']['sand']['sak'] }})</li>
            <li>Air: {{ $summary['materials']['water']['liters'] }}</li>
        </ul>
    </div>

    <div class="alert alert-success">
        <strong>Total Estimasi Biaya Material: {{ $summary['total_cost'] }}</strong>
    </div>

    <form action="{{ route('material-calculations.store') }}" method="POST" class="button-actions" style="justify-content: flex-end; gap:12px; margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
        @csrf

        {{-- Kirim ulang semua data form asli + flag confirm_save --}}
        <input type="hidden" name="confirm_save" value="1">
        @foreach($formData as $key => $value)
            @if(is_array($value))
                @foreach($value as $k => $v)
                    <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <button type="button" class="btn btn-cancel" onclick="window.history.back();">
            <i class="bi bi-arrow-left"></i> Ubah Input
        </button>
        <a href="{{ route('material-calculations.index') }}" class="btn btn-cancel">
            <i class="bi bi-x-lg"></i> Jangan Simpan
        </a>
        <button type="submit" class="btn btn-submit">
            <i class="bi bi-check-lg"></i> Simpan Perhitungan
        </button>
    </form>
</div>
@endsection

@push('styles')
<style data-modal-style="material-calculation">
    .card { 
        max-width: 700px !important; 
        width: 100% !important;
        background: #fff; 
        padding: 24px; 
        border-radius: 8px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        margin: 10px auto; 
    }

    .form-title { 
        font-size: 18px; 
        font-weight: 700; 
        color: #1e293b; 
        margin-bottom: 20px; 
        padding-bottom: 12px; 
        border-bottom: 1px solid #e2e8f0; 
    }

    .btn { 
        padding: 10px 24px; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 14px; 
        font-weight: 600; 
        transition: all 0.2s; 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        font-family: inherit; 
    }

    .btn:hover { 
        transform: translateY(-1px); 
        box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
    }

    .btn-cancel { 
        background: #fff; 
        color: #64748b; 
        border: 1px solid #cbd5e1; 
    }

    .btn-cancel:hover { 
        background: #f8fafc; 
    }

    .btn-submit { 
        background: #16a34a; 
        color: #fff; 
        border: none; 
    }

    .btn-submit:hover { 
        background: #15803d; 
    }
</style>
@endpush
