@extends('layouts.app')

@section('title', 'Detail Material')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Detail Material</h2>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('materials.edit', $material->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('materials.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div style="display: flex; gap: 40px;">
        <div style="flex: 1;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px; font-weight: 600; width: 200px; border-bottom: 1px solid #ddd;">Nama Material</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->material_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Jenis</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->type ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Merek</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->brand ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Sub Merek</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->sub_brand ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Code Warna</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->color_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Nama Warna</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->color_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Bentuk</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->form ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Kemasan</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($material->package_unit)
                            {{ $material->package_weight_gross }} {{ $material->package_unit }} (Kotor)
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Berat Bersih</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($material->package_weight_net)
                            {{ number_format($material->package_weight_net, 2, ',', '.') }} Kg
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Volume</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($material->volume)
                            {{ number_format($material->volume, 2, ',', '.') }} {{ $material->volume_unit }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Toko</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->store ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Alamat Singkat</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->short_address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Alamat Lengkap</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $material->address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Harga Beli</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($material->purchase_price)
                            Rp {{ number_format($material->purchase_price, 0, ',', '.') }} / {{ $material->price_unit }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600;">Harga Komparasi per Kg</td>
                    <td style="padding: 10px;">
                        @if($material->comparison_price_per_kg)
                            <strong style="color: #27ae60;">Rp {{ number_format($material->comparison_price_per_kg, 0, ',', '.') }} / Kg</strong>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if($material->photo_url)
        <div style="width: 300px;">
            <div style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; position: relative;">
                <img src="{{ $material->photo_url }}"
                     alt="{{ $material->material_name }}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                     style="width: 100%; border-radius: 4px;">
                <div style="display: none; align-items: center; justify-content: center; min-height: 200px; color: #95a5a6; flex-direction: column;">
                    <div style="font-size: 48px;">📷</div>
                    <div style="margin-top: 10px;">Gambar tidak tersedia</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
