@extends('layouts.app')

@section('title', 'Semua Material')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Semua Material</h2>
        <div style="display: flex; gap: 12px;">
            <button type="button" id="openMaterialChoiceModal" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
            <a href="{{ route('materials.settings') }}" class="btn btn-secondary">
                <i class="bi bi-gear"></i> Pengaturan Tampilan
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <form action="{{ route('materials.index') }}" method="GET" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari semua material..."
                       style="width: 100%; padding: 11px 14px 11px 40px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari
            </button>
            @if(request('search'))
                <a href="{{ route('materials.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </div>
    </form>

    @if(count($materials) > 0)
        @foreach($materials as $material)
            <div class="material-section" style="margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">
                    <h3 style="margin: 0; color: #891313; font-size: 18px; font-weight: 700;">
                        {{ $material['label'] }}
                        <span style="color: #94a3b8; font-size: 14px; font-weight: 400; margin-left: 8px;">
                            ({{ $material['count'] }} items)
                        </span>
                    </h3>
                    <a href="{{ route($material['type'] . 's.index') }}" class="btn btn-sm btn-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                @if($material['data']->count() > 0)
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    @if($material['type'] == 'brick')
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Bentuk</th>
                                        <th>Dimensi (cm)</th>
                                        <th>Volume (M3)</th>
                                        <th>Toko</th>
                                        <th>Alamat Singkat</th>
                                        <th>Harga / Buah</th>
                                        <th>Harga / M3</th>
                                    @elseif($material['type'] == 'cat')
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Sub Merek</th>
                                        <th style="text-align: right;">Code</th>
                                        <th style="text-align: left;">Warna</th>
                                        <th>Kemasan</th>
                                        <th>Volume</th>
                                        <th style="text-align: left;">Berat Bersih</th>
                                        <th>Toko</th>
                                        <th style="text-align: left;">Alamat Singkat</th>
                                        <th>Harga</th>
                                        <th>Harga / Kg</th>
                                    @elseif($material['type'] == 'cement')
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Sub Merek</th>
                                        <th style="text-align: right">Code</th>
                                        <th style="text-align: left">Warna</th>
                                        <th>Kemasan</th>
                                        <th>Berat</th>
                                        <th>Toko</th>
                                        <th>Alamat Singkat</th>
                                        <th>Harga</th>
                                        <th>Harga / Kg</th>
                                    @elseif($material['type'] == 'sand')
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Kemasan</th>
                                        <th>Dimensi (M)</th>
                                        <th>Volume (M3)</th>
                                        <th>Toko</th>
                                        <th>Alamat Singkat</th>
                                        <th>Harga</th>
                                        <th>Harga / M3</th>
                                    @endif
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($material['data'] as $index => $item)
                                <tr>
                                    <td style="text-align: center; font-weight: 500; color: #64748b;">
                                        {{ $material['data']->firstItem() + $index }}
                                    </td>
                                    @if($material['type'] == 'brick')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Bata</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">
                                            @if($item->photo_url)
                                                <img src="{{ $item->photo_url }}" alt="Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                                            @else
                                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                                            @endif
                                        </td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->form ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 12px;">
                                            @if($item->dimension_length && $item->dimension_width && $item->dimension_height)
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 1, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format($item->dimension_width, 1, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format($item->dimension_height, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
                                        <td>
                                            @if($item->price_per_piece)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->price_per_piece, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cat')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Cat</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">
                                            @if($item->photo_url)
                                                <img src="{{ $item->photo_url }}" alt="Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                                            @else
                                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                                            @endif
                                        </td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 12px; text-align:right;">{{ $item->color_code ?? '-' }}</td>
                                        <td style="color: #475569; text-align:left;">{{ $item->color_name ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->volume)
                                                {{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }} {{ $item->volume_unit }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 13px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
                                        <td>
                                            @if($item->purchase_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->purchase_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cement')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Semen</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">
                                            @if($item->photo_url)
                                                <img src="{{ $item->photo_url }}" alt="Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                                            @else
                                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                                            @endif
                                        </td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 12px; text-align:right;">{{ $item->code ?? '-' }}</td>
                                        <td style="color: #475569; text-align:left;">{{ $item->color ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 13px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'sand')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Pasir</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="text-align: center;">
                                            @if($item->photo_url)
                                                <img src="{{ $item->photo_url }}" alt="Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                                            @else
                                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                                            @endif
                                        </td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="color: #475569; font-size: 12px;">
                                            @if($item->dimension_length && $item->dimension_width && $item->dimension_height)
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 2, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format($item->dimension_width, 2, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format($item->dimension_height, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route($material['type'] . 's.show', $item->id) }}" class="btn btn-primary btn-sm open-modal" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route($material['type'] . 's.edit', $item->id) }}" class="btn btn-warning btn-sm open-modal" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" style="margin-top: 16px;">
                        {{ $material['data']->appends(request()->query())->links('pagination::simple-default') }}
                    </div>
                @else
                    <div style="padding: 40px; text-align: center; color: #94a3b8;">
                        Tidak ada data {{ strtolower($material['label']) }}
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada material yang ditampilkan</p>
            <p style="font-size: 14px; color: #94a3b8;">Atur material yang ingin ditampilkan di pengaturan</p>
            <a href="{{ route('materials.settings') }}" class="btn btn-primary" style="margin-top: 16px;">
                <i class="bi bi-gear"></i> Pengaturan Tampilan
            </a>
        </div>
    @endif
</div>

<!-- Material Choice Modal -->
<div id="materialChoiceModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content" style="width: 600px;">
        <div class="floating-modal-header">
            <h2>Pilih Jenis Material</h2>
            <button class="floating-modal-close" id="closeMaterialChoiceModal">&times;</button>
        </div>
        <div class="floating-modal-body">
            <p style="color: #64748b; margin-bottom: 24px;">Pilih jenis material yang ingin Anda tambahkan:</p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <a href="{{ route('bricks.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🧱</div>
                    <div class="material-choice-label">Bata</div>
                    <div class="material-choice-desc">Tambah data bata</div>
                </a>
                <a href="{{ route('cats.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🎨</div>
                    <div class="material-choice-label">Cat</div>
                    <div class="material-choice-desc">Tambah data cat</div>
                </a>
                <a href="{{ route('cements.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🏗️</div>
                    <div class="material-choice-label">Semen</div>
                    <div class="material-choice-desc">Tambah data semen</div>
                </a>
                <a href="{{ route('sands.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">⛱️</div>
                    <div class="material-choice-label">Pasir</div>
                    <div class="material-choice-desc">Tambah data pasir</div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Floating Modal -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Detail Material</h2>
            <button class="floating-modal-close" id="closeModal">&times;</button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <div style="text-align: center; padding: 60px; color: #94a3b8;">
                <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                <div style="font-weight: 500;">Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles - Modern & Minimalist */
.floating-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

.floating-modal.active {
    display: block;
}

.floating-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.floating-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
    max-width: 95%;
    max-height: 95vh;
    width: 1200px;
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.floating-modal-header {
    padding: 24px 32px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
}

.floating-modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #94a3b8;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.floating-modal-close:hover {
    background: #fee2e2;
    color: #ef4444;
}

.floating-modal-body {
    padding: 32px;
    overflow-y: auto;
    max-height: calc(95vh - 90px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translate(-50%, -48%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

/* Scrollbar styling */
.floating-modal-body::-webkit-scrollbar {
    width: 10px;
}

.floating-modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}

.floating-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}

.floating-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Input focus styles */
input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
}

/* Material Choice Cards */
.material-choice-card {
    display: block;
    padding: 24px;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.material-choice-card:hover {
    border-color: #891313;
    background: #fff5f5;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(137, 19, 19, 0.15);
}

.material-choice-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.material-choice-label {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
}

.material-choice-desc {
    font-size: 13px;
    color: #64748b;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = modal.querySelector('.floating-modal-backdrop');

    function interceptFormSubmit() {
        const form = modalBody.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Let form submit normally
            });
        }
    }

    // Helper function to determine material type and action from URL
    function getMaterialInfo(url) {
        let materialType = '';
        let action = '';
        let materialLabel = 'Material';

        if (url.includes('/bricks/')) {
            materialType = 'brick';
            materialLabel = 'Bata';
        } else if (url.includes('/cats/')) {
            materialType = 'cat';
            materialLabel = 'Cat';
        } else if (url.includes('/cements/')) {
            materialType = 'cement';
            materialLabel = 'Semen';
        } else if (url.includes('/sands/')) {
            materialType = 'sand';
            materialLabel = 'Pasir';
        }

        if (url.includes('/create')) {
            action = 'create';
        } else if (url.includes('/edit')) {
            action = 'edit';
        } else if (url.includes('/show')) {
            action = 'show';
        }

        return { materialType, action, materialLabel };
    }

    // Helper function to load material-specific form script
    function loadMaterialFormScript(materialType, modalBody) {
        const scriptProperty = `${materialType}FormScriptLoaded`;
        const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

        if (!window[scriptProperty]) {
            const script = document.createElement('script');
            script.src = `/js/${materialType}-form.js`;
            script.onload = () => {
                window[scriptProperty] = true;
                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        window[initFunctionName](modalBody);
                    }
                    interceptFormSubmit();
                }, 100);
            };
            document.head.appendChild(script);
        } else {
            setTimeout(() => {
                if (typeof window[initFunctionName] === 'function') {
                    window[initFunctionName](modalBody);
                }
                interceptFormSubmit();
            }, 100);
        }
    }

    document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const { materialType, action, materialLabel } = getMaterialInfo(url);

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update title based on action
            if (action === 'create') {
                modalTitle.textContent = `Tambah ${materialLabel} Baru`;
            } else if (action === 'edit') {
                modalTitle.textContent = `Edit ${materialLabel}`;
            } else if (action === 'show') {
                modalTitle.textContent = `Detail ${materialLabel}`;
            } else {
                modalTitle.textContent = materialLabel;
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('form') || doc.querySelector('.card') || doc.body;
                modalBody.innerHTML = content ? content.outerHTML : html;

                // Load material-specific form script if needed
                if (materialType && (action === 'create' || action === 'edit')) {
                    loadMaterialFormScript(materialType, modalBody);
                } else {
                    interceptFormSubmit();
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                console.error('Fetch error:', err);
            });
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Material Choice Modal
    const materialChoiceModal = document.getElementById('materialChoiceModal');
    const openMaterialChoiceBtn = document.getElementById('openMaterialChoiceModal');
    const closeMaterialChoiceBtn = document.getElementById('closeMaterialChoiceModal');
    const materialChoiceBackdrop = materialChoiceModal.querySelector('.floating-modal-backdrop');

    // Open material choice modal
    openMaterialChoiceBtn.addEventListener('click', function() {
        materialChoiceModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // Close material choice modal
    function closeMaterialChoiceModal() {
        materialChoiceModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    closeMaterialChoiceBtn.addEventListener('click', closeMaterialChoiceModal);
    materialChoiceBackdrop.addEventListener('click', closeMaterialChoiceModal);

    // Close material choice modal on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && materialChoiceModal.classList.contains('active')) {
            closeMaterialChoiceModal();
        }
    });

    // When user clicks a material choice, close the choice modal first
    document.querySelectorAll('.material-choice-card').forEach(card => {
        card.addEventListener('click', function(e) {
            closeMaterialChoiceModal();
            // The open-modal class will handle opening the form modal
        });
    });
});
</script>
@endsection
