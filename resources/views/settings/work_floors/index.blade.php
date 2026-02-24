@extends('layouts.app')

@section('title', 'Pengaturan Lantai')

@section('content')
    <h2 class="mb-3">Pengaturan Lantai</h2>

    <div class="card mb-3">
        <form action="{{ route('settings.work-floors.store') }}" method="POST" class="d-flex gap-2 align-items-end">
            @csrf
            <div class="flex-grow-1">
                <label class="form-label mb-1">Nama Lantai</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Lantai 1" required>
            </div>
            <button type="submit" class="btn btn-primary-glossy">Tambah</button>
        </form>
    </div>

    <div class="card mb-3">
        <form action="{{ route('settings.work-floors.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari lantai...">
            <button type="submit" class="btn btn-secondary-glossy">Cari</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">No</th>
                    <th>Lantai</th>
                    <th style="width: 320px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($floors as $index => $floor)
                    <tr>
                        <td>{{ $floors->firstItem() + $index }}</td>
                        <td>{{ $floor->name }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <form action="{{ route('settings.work-floors.update', $floor) }}" method="POST" class="d-flex gap-2 flex-grow-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $floor->name }}" class="form-control form-control-sm" required>
                                    <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                </form>
                                <form action="{{ route('settings.work-floors.destroy', $floor) }}" method="POST" data-confirm="Hapus lantai ini?" data-confirm-ok="Hapus" data-confirm-cancel="Batal">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada data lantai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $floors->links() }}
    </div>
@endsection

