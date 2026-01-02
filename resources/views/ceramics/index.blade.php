@extends('layouts.app')

@section('title', 'Database Keramik')

@section('content')
<div class="card">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;">
        <button
            type="button"
            class="btn btn-primary btn-sm"
            style="display: inline-flex; align-items: center; gap: 6px;"
            onclick="window.location.href='{{ route('materials.index') }}'">
            <i class="bi bi-chevron-left" style="color: #ffffff; font-size: 1.2rem;"></i>
        </button>

        <h2 style="margin: 0; flex-shrink: 0;">Database Keramik</h2>

        <form id="search-form" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                    id="search-input"
                    name="search"
                    placeholder="Cari merek, kode, warna, toko..."
                    value="{{ request('search') }}"
                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: all 0.2s;"
                    onfocus="this.style.borderColor='#4f46e5'; this.style.boxShadow='0 0 0 4px rgba(79, 70, 229, 0.1)';"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">
            </div>
            
            <button type="button" 
                    onclick="openFloatingModal('{{ route('ceramics.create') }}', 'Tambah Data Keramik')"
                    class="btn btn-primary" 
                    style="padding: 11px 20px; display: flex; align-items: center; gap: 8px; font-weight: 500; border-radius: 10px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: none; box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2); text-decoration: none; color: white; cursor: pointer;">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
        </div>
    @endif

    <div id="table-container" style="overflow-x: auto; border: 1px solid #f1f5f9; border-radius: 12px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Produk Info</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Spesifikasi</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Harga</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Toko</th>
                    <th style="padding: 16px; text-align: center; font-weight: 600; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ceramics as $ceramic)
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; overflow: hidden; background: #f1f5f9; flex-shrink: 0; border: 1px solid #e2e8f0;">
                                @if($ceramic->photo)
                                    <img src="{{ Storage::url($ceramic->photo) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #0f172a;">{{ $ceramic->brand }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $ceramic->sub_brand }}</div>
                                <div style="font-size: 11px; color: #6366f1; background: #eef2ff; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">{{ $ceramic->type }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 16px;">
                        <div style="font-family: monospace; font-size: 13px; color: #334155; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 4px;">
                            {{ $ceramic->dimension_length }}x{{ $ceramic->dimension_width }}
                        </div>
                        <div style="font-size: 12px; color: #64748b;">Isi: {{ $ceramic->pieces_per_package }} / {{ $ceramic->packaging }}</div>
                    </td>
                    <td style="padding: 16px;">
                        <div style="font-weight: 700; color: #0f172a;">Rp {{ number_format($ceramic->price_per_package, 0, ',', '.') }}</div>
                        <div style="font-size: 12px; color: #16a34a; font-weight: 500;">
                            @if($ceramic->comparison_price_per_m2)
                                {{ number_format($ceramic->comparison_price_per_m2, 0, ',', '.') }}/m²
                            @endif
                        </div>
                    </td>
                    <td style="padding: 16px;">
                        <div style="font-weight: 500; color: #334155;">{{ $ceramic->store ?? '-' }}</div>
                        <div style="font-size: 12px; color: #94a3b8; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ceramic->address }}</div>
                    </td>
                    <td style="padding: 16px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 8px;">
                            <button onclick="openFloatingModal('{{ route('ceramics.show', $ceramic) }}', 'Detail Keramik')" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe; transition: all 0.2s; cursor: pointer;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button onclick="openFloatingModal('{{ route('ceramics.edit', $ceramic) }}', 'Edit Keramik')" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f5f3ff; color: #8b5cf6; border: 1px solid #ede9fe; transition: all 0.2s; cursor: pointer;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('ceramics.destroy', $ceramic) }}" method="POST" onsubmit="return confirm('Hapus data ini?');" style="display: inline;">
                                @csrf @method('DELETE')
                                <button type="submit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; cursor: pointer; transition: all 0.2s;" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📦</div>
                        <div>Belum ada data keramik ditemukan.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $ceramics->withQueryString()->links() }}
    </div>
</div>

<div id="floatingModal" style="position: fixed; inset: 0; z-index: 50; display: none; justify-content: center; align-items: center;">
    <div id="modalBackdrop" style="position: absolute; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease;"></div>
    
    <div id="modalContent" style="position: relative; background: white; width: 100%; max-width: 1100px; max-height: 90vh; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: scale(0.95); opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); overflow: hidden; display: flex; flex-direction: column;">
        
        <div style="padding: 20px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff; z-index: 10;">
            <h3 id="modalTitle" style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Form Data</h3>
            <button type="button" onclick="closeFloatingModal()" style="background: transparent; border: none; cursor: pointer; color: #94a3b8; padding: 8px; border-radius: 50%; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-x-lg" style="font-size: 20px;"></i>
            </button>
        </div>

        <div id="modalBody" style="padding: 30px; overflow-y: auto; background: #f8fafc; flex: 1;">
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search Debounce Logic
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('search-form').submit();
        }, 500);
    });

    // Modal Logic
    function openFloatingModal(url, title) {
        document.getElementById('modalTitle').innerText = title;
        const modal = document.getElementById('floatingModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');
        const body = document.getElementById('modalBody');

        modal.style.display = 'flex';
        // Animasi Masuk
        setTimeout(() => {
            backdrop.style.opacity = '1';
            content.style.opacity = '1';
            content.style.transform = 'scale(1)';
        }, 10);

        // Fetch Content
        fetch(url)
            .then(response => response.text())
            .then(html => {
                body.innerHTML = html;
                // Re-run scripts in new HTML
                const scripts = body.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    if(oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.textContent = oldScript.textContent;
                    }
                    document.body.appendChild(newScript);
                });
            })
            .catch(err => {
                body.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">Gagal memuat konten.</div>';
            });
    }

    window.closeFloatingModal = function() {
        const modal = document.getElementById('floatingModal');
        const backdrop = document.getElementById('modalBackdrop');
        const content = document.getElementById('modalContent');

        backdrop.style.opacity = '0';
        content.style.opacity = '0';
        content.style.transform = 'scale(0.95)';

        setTimeout(() => {
            modal.style.display = 'none';
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border" role="status"></div></div>';
        }, 300);
    }

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeFloatingModal();
    });
</script>
@endsection