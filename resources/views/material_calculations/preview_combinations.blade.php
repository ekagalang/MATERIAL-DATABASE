@extends('layouts.app')

@section('title', 'Pilih Kombinasi Material')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary"><i class="bi bi-grid-1x2 me-2"></i>Pilih Kombinasi Material</h2>
            
            {{-- PERBAIKAN LOGIC HEADER --}}
            @php
                $headerTitle = '';
                if(isset($projects) && count($projects) > 0) {
                    if (count($projects) == 1) {
                        // Jika 1 bata, ambil nama bata tersebut
                        $firstBrick = $projects[0]['brick'];
                        $headerTitle = "untuk bata <strong>{$firstBrick->brand}</strong>";
                    } else {
                        // Jika banyak bata
                        $count = count($projects);
                        $headerTitle = "untuk <strong>{$count} Jenis Bata</strong> yang dipilih";
                    }
                }
                
                // Hitung luas untuk display
                $area = 0;
                if(isset($requestData['wall_length']) && isset($requestData['wall_height'])) {
                    $area = $requestData['wall_length'] * $requestData['wall_height'];
                }
            @endphp

            <p class="text-muted mb-0">
                Menampilkan opsi kombinasi {!! $headerTitle !!}
                (Luas: {{ number_format($area, 2) }} m²)
            </p>
        </div>
        
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali Filter
        </a>
    </div>

    @if(empty($projects))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i> Tidak ditemukan data.
        </div>
    @else
        
        {{-- TABS NAVIGATION (Hanya muncul jika lebih dari 1 Bata) --}}
        @if(count($projects) > 1)
            <ul class="nav nav-tabs mb-4" id="brickTabs" role="tablist">
                @foreach($projects as $index => $project)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $index === 0 ? 'active' : '' }} fw-bold" 
                                id="brick-tab-{{ $index }}" 
                                data-bs-toggle="tab" 
                                data-bs-target="#brick-content-{{ $index }}" 
                                type="button" role="tab">
                            {{ $project['brick']->brand }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- TAB CONTENTS --}}
        <div class="tab-content" id="brickTabsContent">
            @foreach($projects as $index => $project)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                     id="brick-content-{{ $index }}" role="tabpanel">
                    
                    {{-- INFO BATA --}}
                    <div class="alert alert-light border-start border-4 border-danger shadow-sm mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-danger fs-3"><i class="bi bi-bricks"></i></div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $project['brick']->brand }} - {{ $project['brick']->type }}</h5>
                                <small class="text-muted">Dimensi: {{ $project['brick']->dimension_length }}x{{ $project['brick']->dimension_width }}x{{ $project['brick']->dimension_height }} cm | Harga: Rp {{ number_format($project['brick']->price_per_piece) }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- LIST KOMBINASI --}}
                    @if(empty($project['combinations']))
                        <div class="alert alert-warning">Tidak ada kombinasi material yang cocok.</div>
                    @else
                        @foreach($project['combinations'] as $groupName => $items)
                            <div class="card mb-4 shadow-sm border-0">
                                <div class="card-header bg-white py-3">
                                    <h6 class="mb-0 fw-bold text-dark border-start border-4 border-primary ps-3">
                                        <i class="bi bi-collection me-2"></i>{{ $groupName }}
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Semen</th>
                                                    <th>Pasir</th>
                                                    <th class="text-center">Total Biaya</th>
                                                    <th class="text-end pe-4">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $item)
                                                    <tr>
                                                        <td class="ps-4">
                                                            <div class="fw-bold">{{ $item['cement']->brand }}</div>
                                                            <small class="text-muted">{{ $item['cement']->package_weight_net }} Kg - Rp {{ number_format($item['cement']->package_price) }}</small>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold">{{ $item['sand']->brand ?? 'Pasir Standar' }}</div>
                                                            <small class="text-muted">{{ $item['sand']->sand_name }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="fw-bold text-success fs-5">Rp {{ number_format($item['total_cost'], 0, ',', '.') }}</div>
                                                            <small class="text-muted">Total Pekerjaan</small>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            {{-- FORM SUBMIT --}}
                                                            <form action="{{ route('material-calculations.store') }}" method="POST">
                                                                @csrf
                                                                {{-- Inject Data Request --}}
                                                                @foreach($requestData as $key => $value)
                                                                    @if($key != '_token' && $key != 'cement_id' && $key != 'sand_id' && $key != 'brick_ids' && $key != 'brick_id')
                                                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                                    @endif
                                                                @endforeach
                                                                
                                                                {{-- Data Spesifik Item Ini --}}
                                                                <input type="hidden" name="brick_id" value="{{ $project['brick']->id }}">
                                                                <input type="hidden" name="cement_id" value="{{ $item['cement']->id }}">
                                                                <input type="hidden" name="sand_id" value="{{ $item['sand']->id }}">
                                                                <input type="hidden" name="price_filter" value="custom"> 
                                                                <input type="hidden" name="confirm_save" value="1"> 

                                                                <button type="submit" class="btn btn-primary rounded shadow" title="Pilih Kombinasi Ini">
                                                                    <i class="bi bi-arrow-right"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush