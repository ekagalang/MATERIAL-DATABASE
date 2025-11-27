@extends('layouts.app')

@section('content')
<div class="card">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-history text-primary"></i> 
                        Riwayat Perhitungan
                    </h2>
                    <p class="text-muted mb-0">Daftar semua perhitungan yang pernah dibuat</p>
                </div>
                <div>
                    <a href="{{ route('brick-calculations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Perhitungan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('brick-calculations.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Cari project...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="installation_type">
                            <option value="">-- Semua Jenis --</option>
                            @foreach($installationTypes as $type)
                                <option value="{{ $type->id }}" 
                                        {{ request('installation_type') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               class="form-control" 
                               name="date_from" 
                               value="{{ request('date_from') }}"
                               placeholder="Dari">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               class="form-control" 
                               name="date_to" 
                               value="{{ request('date_to') }}"
                               placeholder="Sampai">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($calculations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Project</th>
                                <th>Dimensi</th>
                                <th>Jenis</th>
                                <th>Bata</th>
                                <th>Semen (50kg)</th>
                                <th>Pasir (m³)</th>
                                <th>Total Biaya</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calculations as $calc)
                            <tr>
                                <td class="text-nowrap">
                                    {{ $calc->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ $calc->project_name ?: '-' }}</strong>
                                    @if($calc->notes)
                                        <br><small class="text-muted">{{ Str::limit($calc->notes, 30) }}</small>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $calc->wall_length }}m × {{ $calc->wall_height }}m
                                    <br><small class="text-muted">({{ number_format($calc->wall_area, 2) }} m²)</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $calc->installationType->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->brick_quantity, 0) }} buah
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->cement_quantity_50kg, 2) }} sak
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->sand_m3, 4) }} m³
                                </td>
                                <td class="text-end text-success">
                                    <strong>Rp {{ number_format($calc->total_material_cost, 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('brick-calculations.show', $calc) }}" 
                                           class="btn btn-outline-primary"
                                           title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('brick-calculations.edit', $calc) }}" 
                                           class="btn btn-outline-warning"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('brick-calculations.destroy', $calc) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus perhitungan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-white">
                    {{ $calculations->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data perhitungan</h5>
                    <p class="text-muted">Silakan buat perhitungan baru</p>
                    <a href="{{ route('brick-calculations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Perhitungan
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection