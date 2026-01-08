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
                            Nama Cat
                        </td>
                        <td style="padding: 14px 20px; 
                                   width: 65%;
                                   border-bottom: 1px solid #e2e8f0;
                                   color: #0f172a;
                                   font-weight: 600;">
                            {{ $cat->cat_name }}
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
                                   color: #1e293b;">
                            {{ $cat->type ?? '-' }}
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
                                   color: #1e293b;">
                            {{ $cat->brand ?? '-' }}
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
                            {{ $cat->sub_brand ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Warna
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->color_name || $cat->color_code)
                                <span style="font-weight: 600;">{{ $cat->color_name ?? '-' }}</span>
                                @if($cat->color_code)
                                    <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">({{ $cat->color_code }})</span>
                                @endif
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
                            Volume
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->volume)
                                <span style="font-weight: 600;">
                                    @format($cat->volume)
                                </span>
                                <span style="color: #64748b; margin-left: 4px;">{{ $cat->volume_unit }}</span>
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
                            Kemasan
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->package_unit)
                                <span style="display: inline-block; 
                                             padding: 4px 10px; 
                                             background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); 
                                             border: 1.5px solid #93c5fd; 
                                             border-radius: 6px;
                                             font-weight: 600;
                                             color: #1e40af;
                                             font-size: 12px;">
                                    {{ $cat->packageUnit->name ?? $cat->package_unit }}
                                </span>
                                @if($cat->package_weight_gross)
                                    <span style="color: #64748b; margin-left: 8px; font-size: 12.5px;">
                                        @format($cat->package_weight_gross) Kg (Kotor)
                                    </span>
                                @endif
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
                            Berat Bersih
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->package_weight_net)
                                <div style="display: inline-block; 
                                            padding: 6px 12px; 
                                            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
                                            border: 1.5px solid #86efac; 
                                            border-radius: 8px;">
                                    <span style="font-weight: 700; color: #15803d;">
                                        @format($cat->package_weight_net)
                                    </span>
                                    <span style="font-weight: 600; color: #16a34a; font-size: 12px;"> Kg</span>
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
                                   font-size: 13px;
                                   text-align: left;">
                            Harga
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->purchase_price)
                                <span style="font-weight: 600; color: #64748b;">Rp</span>
                                <span style="font-weight: 700; color: #0f172a;">
                                    {{ number_format($cat->purchase_price, 0, ',', '.') }}
                                </span>
                                <span style="color: #94a3b8; font-size: 12px; margin-left: 4px;">/ {{ $cat->price_unit ?? 'unit' }}</span>
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
                            Harga / Kg
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            @if($cat->comparison_price_per_kg)
                                <div style="display: inline-block; 
                                            padding: 8px 16px; 
                                            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); 
                                            border: 1.5px solid #fca5a5; 
                                            border-radius: 10px;">
                                    <span style="font-weight: 600; color: #991b1b; font-size: 13px;">Rp</span>
                                    <span style="font-weight: 700; color: #7f1d1d; font-size: 15px;">
                                        {{ number_format($cat->comparison_price_per_kg, 0, ',', '.') }}
                                    </span>
                                    <span style="font-weight: 600; color: #991b1b; font-size: 12px;">/ Kg</span>
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
                                   font-size: 13px;
                                   text-align: left;">
                            Toko
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $cat->store ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569; 
                                   border-bottom: 1px solid #f1f5f9;
                                   font-size: 13px;
                                   text-align: left;">
                            Alamat Singkat
                        </td>
                        <td style="padding: 14px 20px; 
                                   border-bottom: 1px solid #f1f5f9;
                                   color: #1e293b;">
                            {{ $cat->short_address ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 14px 20px; 
                                   font-weight: 600; 
                                   color: #475569;
                                   font-size: 13px;
                                   text-align: left;">
                            Alamat Lengkap
                        </td>
                        <td style="padding: 14px 20px;
                                   color: #1e293b;
                                   line-height: 1.6;">
                            {{ $cat->address ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan - Foto Produk -->
        @if($cat->photo_url)
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
                    <img src="{{ $cat->photo_url }}"
                         alt="{{ $cat->cat_name }}"
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
            class="btn btn-secondary-glossy " 
            onclick="window.parent.document.getElementById('closeModal').click()"
            style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); 
                   color: #ffffff; 
                   display: flex; 
                   align-items: center; 
                   gap: 8px;">
        <i class="bi bi-x-lg"></i> Tutup
    </button>
</div>