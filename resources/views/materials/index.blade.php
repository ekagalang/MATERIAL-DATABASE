@extends('layouts.app')

@section('title', 'Database Material')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Database Material</h2>
        <a href="{{ route('materials.create') }}" class="btn btn-success">+ Tambah Material</a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('materials.index') }}" method="GET" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari material, merek, warna, toko..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search'))
                <a href="{{ route('materials.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    @if($materials->count() > 0)
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Material</th>
                        <th>Jenis</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Merek</th>
                        <th>Sub Merek</th>
                        <th>Code Warna</th>
                        <th>Nama Warna</th>
                        <th>Kemasan</th>
                        <th>Volume</th>
                        <th>Berat Bersih</th>
                        <th>Toko</th>
                        <th>Alamat Singkat</th>
                        <th style="text-align: right;">Harga Beli</th>
                        <th style="text-align: right;">Harga/Kg</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materials as $index => $material)
                    <tr>
                        <td>{{ $materials->firstItem() + $index }}</td>
                        <td><strong>Cat</strong></td>
                        <td>{{ $material->type ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($material->photo_url)
                                <img src="{{ $material->photo_url }}"
                                     alt="Photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span style="color: #95a5a6; display: none;">📷</span>
                            @else
                                <span style="color: #95a5a6;">-</span>
                            @endif
                        </td>
                        <td>{{ $material->brand ?? '-' }}</td>
                        <td>{{ $material->sub_brand ?? '-' }}</td>
                        <td>{{ $material->color_code ?? '-' }}</td>
                        <td>{{ $material->color_name ?? '-' }}</td>
                        <td>
                            @if($material->package_unit)
                                {{ $material->package_weight_gross }} {{ $material->package_unit }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($material->volume)
                                {{ number_format($material->volume, 2, ',', '.') }} {{ $material->volume_unit }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($material->package_weight_net)
                                {{ number_format($material->package_weight_net, 2, ',', '.') }} Kg
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $material->store ?? '-' }}</td>
                        <td>{{ $material->short_address ?? '-' }}</td>
                        <td style="text-align: right;">
                            @if($material->purchase_price)
                                Rp {{ number_format($material->purchase_price, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($material->comparison_price_per_kg)
                                Rp {{ number_format($material->comparison_price_per_kg, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="actions" style="justify-content: center;">
                                <a href="{{ route('materials.show', $material->id) }}" class="btn btn-primary btn-sm">Detail</a>
                                <a href="{{ route('materials.edit', $material->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus material ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $materials->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>{{ request('search') ? 'Tidak ada material yang sesuai dengan pencarian' : 'Belum ada data material' }}</p>
        </div>
    @endif
</div>
@endsection
