<div class="card">
    <div style="display: flex; gap: 32px;">
        <!-- Kolom Kiri - Detail Informasi -->
        <div style="flex: 1;">
            <div style="background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%); 
                        border: 1px solid #f1f5f9; 
                        border-radius: 12px; 
                        overflow: hidden;">
                <table style="width: 100%; font-size: 13.5px;">
                    <tr style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <td style="padding: 14px 20px; 
                                   font-weight: 700; 
                                   width: 35%; 
                                   color: #334155; 
                                   border-bottom: 1px solid #e2e8f0;
                                   font-size: 12px;
                                   text-transform: uppercase;
                                   letter-spacing: 0.5px;
                                   text-align: left;">
                            Material
                        </td>
                        <td style="padding: 14px 20px; 
                                   width: 65%;
                                   border-bottom: 1px solid #e2e8f0;
                                   color: #0f172a;
                                   font-weight: 600;">
                            {{ $ceramic->material_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Jenis
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #4f46e5;
                                   font-weight: 600;">
                            {{ $ceramic->type ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Dimensi (P x L x T)
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($ceramic->dimension_length && $ceramic->dimension_width)
                                <span style="font-weight: 600;">
                                    @format($ceramic->dimension_length) cm 
                                    <span style="color: #cbd5e1; font-weight: 300;">×</span>
                                    @format($ceramic->dimension_width) cm
                                    @if($ceramic->dimension_thickness)
                                        <span style="color: #cbd5e1; font-weight: 300;">×</span>
                                        @format($ceramic->dimension_thickness * 10) mm
                                    @endif
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Merek
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;
                                   font-weight: 600;">
                            {{ $ceramic->brand ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Sub Merek
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $ceramic->sub_brand ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Permukaan
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $ceramic->surface ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Kode Lengkap
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            <span style="font-weight: 600;">{{ $ceramic->code ?? '-' }}</span> 
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Warna (Corak)
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            <span style="font-weight: 600;">{{ $ceramic->color ?? '-' }}</span> 
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Bentuk
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $ceramic->form ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Isi
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-weight: 600; font-size: 13px;">
                                    {{ $ceramic->pieces_per_package ?? 0 }} Lbr / {{ $ceramic->packaging ?? 'Dus' }}
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Luas / {{ $ceramic->packaging ?? 'Dus' }}
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="display: inline-block; 
                                            padding: 6px 12px; 
                                            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
                                            border: 1.5px solid #86efac; 
                                            border-radius: 8px;">
                                    <span style="font-weight: 700; color: #15803d;">
                                        @format($ceramic->coverage_per_package)
                                    </span>
                                    <span style="font-weight: 600; color: #16a34a; font-size: 12px;"> M2</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Toko
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;
                                   font-weight: 600;">
                            {{ $ceramic->store ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Alamat
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;
                                   line-height: 1.6;">
                            {{ $ceramic->address ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Harga Beli
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($ceramic->price_per_package)
                                <span style="font-weight: 600; color: #64748b;">Rp</span>
                                <span style="font-weight: 700; color: #0f172a;">
                                    @price($ceramic->price_per_package)
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569;
                                   font-size: 13px;
                                   text-align: left;">
                            Harga Komparasi
                        </td>
                        <td style="padding: 14px 20px;
                                   color: #1e293b;">
                            @if($ceramic->comparison_price_per_m2)
                                <div style="display: inline-block; 
                                            padding: 8px 16px; 
                                            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); 
                                            border: 1.5px solid #fca5a5; 
                                            border-radius: 10px;">
                                    <span style="font-weight: 600; color: #991b1b; font-size: 13px;">Rp</span>
                                    <span style="font-weight: 700; color: #7f1d1d; font-size: 15px;">
                                        @price($ceramic->comparison_price_per_m2)
                                    </span>
                                    <span style="font-weight: 600; color: #991b1b; font-size: 12px;">/ M2</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan - Foto Produk -->
        <div style="flex: 0 0 360px; max-width: 360px;">
            @php
                $photoUrl = $ceramic->photo ? Storage::url($ceramic->photo) : null;
            @endphp
            
            @if($photoUrl)
            <div style="border: 2px solid #f1f5f9; 
                        border-radius: 16px; 
                        padding: 8px; 
                        background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);">
                <div style="border-radius: 12px; 
                            overflow: hidden; 
                            position: relative; 
                            background: #f8fafc;">
                    <img src="{{ $photoUrl }}"
                         alt="{{ $ceramic->brand }}"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         style="width: 100%; 
                                height: auto; 
                                display: block;
                                border-radius: 8px;">
                    <div style="display: none; 
                                align-items: center; 
                                justify-content: center; 
                                min-height: 300px; 
                                color: #cbd5e1; 
                                flex-direction: column;
                                text-align: center;">
                        <div style="font-size: 64px; opacity: 0.4; margin-bottom: 16px;">📷</div>
                        <div style="font-size: 14px; font-weight: 600; color: #94a3b8;">Gambar tidak tersedia</div>
                    </div>
                </div>
            </div>
            @else
            <div style="border: 2px dashed #e2e8f0; 
                        border-radius: 16px; 
                        padding: 8px; 
                        background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);">
                <div style="border-radius: 12px; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            min-height: 300px; 
                            color: #cbd5e1; 
                            flex-direction: column;
                            text-align: center;
                            background: #f8fafc;">
                    <div style="font-size: 64px; opacity: 0.4; margin-bottom: 16px;">📷</div>
                    <div style="font-size: 14px; font-weight: 600; color: #94a3b8;">Tidak ada foto</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Tombol Tutup -->
<div style="display: flex; 
            justify-content: flex-end; 
            margin-top: 24px; 
            padding-top: 24px; 
            border-top: 1px solid #f1f5f9;">
    <button type="button" 
            class="btn btn-secondary-glossy " 
            onclick="closeFloatingModal()"
            style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); 
                   color: #ffffff; 
                   display: flex; 
                   align-items: center; 
                   gap: 8px;
                   border: none;
                   padding: 10px 24px;
                   border-radius: 8px;
                   cursor: pointer;
                   font-weight: 500;">
        <i class="bi bi-x-lg"></i> Tutup
    </button>
</div>
