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
                                   letter-spacing: 0.5px;">
                            Nama Material
                        </td>
                        <td style="padding: 14px 20px; 
                                   width: 65%;
                                   border-bottom: 1px solid #e2e8f0;
                                   color: #0f172a;
                                   font-weight: 600;">
                            {{ $sand->sand_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;">
                            Jenis
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $sand->type ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px;
                                   font-weight: 600;
                                   color: #475569;
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;">
                            Merek
                        </td>
                        <td style="padding: 14px 20px;
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $sand->brand ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px;
                                   font-weight: 600;
                                   color: #475569;
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;">
                            Kemasan
                        </td>
                        <td style="padding: 14px 20px;
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e40af;">
                            @if($sand->package_unit)
                                <div style="display: inline-block;
                                            padding: 6px 14px;
                                            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                                            border: 1.5px solid #93c5fd;
                                            border-radius: 8px;">
                                    <span style="font-weight: 700; color: #1e40af; font-size: 13px;">
                                        {{ $sand->packageUnit->name ?? $sand->package_unit }}
                                    </span>
                                </div>
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
                                   font-size: 13px;">
                            Dimensi Kemasan
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($sand->dimension_length && $sand->dimension_width && $sand->dimension_height)
                                <span style="font-weight: 600;">
                                    {{ rtrim(rtrim(number_format($sand->dimension_length, 2, ',', '.'), '0'), ',') }} M
                                    <span style="color: #cbd5e1; font-weight: 300;">×</span>
                                    {{ rtrim(rtrim(number_format($sand->dimension_width, 2, ',', '.'), '0'), ',') }} M
                                    <span style="color: #cbd5e1; font-weight: 300;">×</span>
                                    {{ rtrim(rtrim(number_format($sand->dimension_height, 2, ',', '.'), '0'), ',') }} M
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
                                   font-size: 13px;">
                            Volume Kemasan
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($sand->package_volume)
                                <div style="display: inline-block; 
                                            padding: 6px 12px; 
                                            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
                                            border: 1.5px solid #86efac; 
                                            border-radius: 8px;">
                                    <span style="font-weight: 700; color: #15803d;">
                                        {{ number_format($sand->package_volume, 6, ',', '.') }}
                                    </span>
                                    <span style="font-weight: 600; color: #16a34a; font-size: 12px;"> M3</span>
                                </div>
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
                                   font-size: 13px;">
                            Harga
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($sand->package_price)
                                <span style="font-weight: 600; color: #64748b;">Rp</span>
                                <span style="font-weight: 700; color: #0f172a;">
                                    {{ number_format($sand->package_price, 0, ',', '.') }}
                                </span>
                                <span style="color: #94a3b8; font-size: 12px;">/ {{ $sand->packageUnit->name ?? $sand->package_unit ?? 'Kemasan' }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569;
                                   font-size: 13px;">
                            Harga / M3
                        </td>
                        <td style="padding: 14px 20px;
                                   color: #1e293b;">
                            @if($sand->comparison_price_per_m3)
                                <div style="display: inline-block; 
                                            padding: 8px 16px; 
                                            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); 
                                            border: 1.5px solid #fca5a5; 
                                            border-radius: 10px;">
                                    <span style="font-weight: 600; color: #991b1b; font-size: 13px;">Rp</span>
                                    <span style="font-weight: 700; color: #7f1d1d; font-size: 15px;">
                                        {{ number_format($sand->comparison_price_per_m3, 0, ',', '.') }}
                                    </span>
                                    <span style="font-weight: 600; color: #991b1b; font-size: 12px;">/ M3</span>
                                </div>
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
                                   font-size: 13px;">
                            Toko
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $sand->store ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;">
                            Alamat Singkat
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $sand->short_address ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;">
                            Alamat Lengkap
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;
                                   line-height: 1.6;">
                            {{ $sand->address ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan - Foto Produk -->
        @if($sand->photo_url)
        <div style="flex: 0 0 360px; max-width: 360px;">
            <div style="border: 2px solid #f1f5f9; 
                        border-radius: 16px; 
                        padding: 8px; 
                        background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);">
                <div style="border-radius: 12px; 
                            overflow: hidden; 
                            position: relative; 
                            background: #f8fafc;">
                    <img src="{{ $sand->photo_url }}"
                         alt="{{ $sand->sand_name }}"
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
        </div>
        @else
        <div style="flex: 0 0 360px; max-width: 360px;">
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
        </div>
        @endif
    </div>
</div>

<!-- Tombol Tutup -->
<div style="display: flex; 
            justify-content: flex-end; 
            margin-top: 24px; 
            padding-top: 24px; 
            border-top: 1px solid #f1f5f9;">
    <button type="button" 
            class="btn btn-secondary" 
            onclick="window.parent.document.getElementById('closeModal').click()"
            style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); 
                   color: #ffffff; 
                   display: flex; 
                   align-items: center; 
                   gap: 8px;">
        <i class="bi bi-x-lg"></i> Tutup
    </button>
</div>