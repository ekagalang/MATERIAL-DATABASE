@extends('layouts.app')

@section('title', 'Database Satuan')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Database Satuan</h2>
        <a href="{{ route('units.create') }}" class="btn btn-success">+ Tambah Satuan</a>
    </div>

    @if($units->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Kode</th>
                    <th>Nama Satuan</th>
                    <th style="width: 150px;">Berat Kemasan (Kg)</th>
                    <th>Keterangan</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($units as $index => $unit)
                <tr>
                    <td>{{ $units->firstItem() + $index }}</td>
                    <td><strong>{{ $unit->code }}</strong></td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ number_format($unit->package_weight, 2, ',', '.') }}</td>
                    <td>{{ $unit->description ?? '-' }}</td>
                    <td>
                        <div class="actions" style="justify-content: center;">
                            <a href="{{ route('units.edit', $unit->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('units.destroy', $unit->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus satuan ini?')">
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

        <div class="pagination">
            {{ $units->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada data satuan</p>
        </div>
    @endif
</div>
@endsection