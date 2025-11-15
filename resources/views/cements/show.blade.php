<div class="card">
    <div style="display: flex; gap: 40px; margin-top: 20px;">
        <!-- Detail Info - Kolom Kiri -->
        <div style="flex: 1;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; font-weight: 600; width: 180px; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Nama Semen</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;"><strong>{{ $cement->cement_name }}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Jenis</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->type ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Merek</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->brand ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Sub Merek</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->sub_brand ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Code</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->code ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Warna</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->color ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Kemasan</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">
                        @if($cement->package_unit)
                            {{ $cement->package_unit }}
                            @if($cement->package_weight_gross)
                                - {{ number_format($cement->package_weight_gross, 2, ',', '.') }} Kg (Kotor)
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Berat Bersih</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">
                        @if($cement->package_weight_net)
                            {{ number_format($cement->package_weight_net, 2, ',', '.') }} Kg
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Harga Kemasan</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">
                        @if($cement->package_price)
                            <strong style="color: #e67e22;">Rp {{ number_format($cement->package_price, 0, ',', '.') }}</strong> / {{ $cement->price_unit ?? 'unit' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Harga Komparasi</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">
                        @if($cement->comparison_price_per_kg)
                            <strong style="color: #27ae60;">Rp {{ number_format($cement->comparison_price_per_kg, 0, ',', '.') }}</strong> / Kg
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Toko</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->store ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; border-bottom: 1px solid #e3e3e0; background: #f8f9fa;">Alamat Singkat</td>
                    <td style="padding: 12px; border-bottom: 1px solid #e3e3e0;">{{ $cement->short_address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 600; background: #f8f9fa;">Alamat Lengkap</td>
                    <td style="padding: 12px;">{{ $cement->address ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Photo - Kolom Kanan -->
        <div style="flex: 0 0 300px; max-width: 300px;">
            @if($cement->photo_url)
                <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #f9f9f9;">
                    <img src="{{ $cement->photo_url }}"
                         alt="{{ $cement->cement_name }}"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         style="width: 100%; height: auto; display: block;">
                    <div style="display: none; align-items: center; justify-content: center; min-height: 300px; color: #95a5a6; flex-direction: column;">
                        <div style="font-size: 48px;">📷</div>
                        <div style="margin-top: 10px;">Gambar tidak tersedia</div>
                    </div>
                </div>
            @else
                <div style="border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; min-height: 300px; color: #95a5a6; flex-direction: column; background: #f9f9f9;">
                    <div style="font-size: 64px;">📷</div>
                    <div style="margin-top: 10px; font-size: 14px;">Tidak ada foto</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Buttons -->
    <div class="btnArea" style="text-align: right; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e3e3e0;">
        <button type="button" class="btn red" onclick="window.parent.document.getElementById('closeModal').click()" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c;">Tutup</button>
    </div>
</div>

<style>
    .card {
        background: #fff;
        padding: 0;
    }
    .card h2 {
        margin: 0 0 20px 0;
        font-size: 20px;
        color: #2c3e50;
        padding-bottom: 15px;
        border-bottom: 2px solid #3498db;
    }
</style>