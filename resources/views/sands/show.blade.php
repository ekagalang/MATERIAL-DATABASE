<div class="card">
    <div style="display: flex; gap: 40px;">
        <div style="flex: 1;">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px; font-weight: 600; width: 200px; border-bottom: 1px solid #ddd;">Nama Material</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->sand_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Jenis</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->type ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Merek</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->brand ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Dimensi Kemasan</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($sand->dimension_length && $sand->dimension_width && $sand->dimension_height)
                            {{ rtrim(rtrim(number_format($sand->dimension_length, 2, ',', '.'), '0'), ',') }} m × 
                            {{ rtrim(rtrim(number_format($sand->dimension_width, 2, ',', '.'), '0'), ',') }} m × 
                            {{ rtrim(rtrim(number_format($sand->dimension_height, 2, ',', '.'), '0'), ',') }} m
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Volume Kemasan</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($sand->package_volume)
                            {{ number_format($sand->package_volume, 6, ',', '.') }} M3
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Toko</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->store ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Alamat Singkat</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->short_address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Alamat Lengkap</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $sand->address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600; border-bottom: 1px solid #ddd;">Harga per Kemasan</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        @if($sand->package_price)
                            Rp {{ number_format($sand->package_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: 600;">Harga Komparasi per M3</td>
                    <td style="padding: 10px;">
                        @if($sand->comparison_price_per_m3)
                            <strong style="color: #27ae60;">Rp {{ number_format($sand->comparison_price_per_m3, 0, ',', '.') }} /M3</strong>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if($sand->photo_url)
        <div style="width: 300px;">
            <div style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; position: relative;">
                <img src="{{ $sand->photo_url }}"
                     alt="{{ $sand->sand_name }}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                     style="width: 100%; border-radius: 4px;">
                <div style="display: none; align-items: center; justify-content: center; min-height: 200px; color: #95a5a6; flex-direction: column;">
                    <div style="font-size: 48px;">📷</div>
                    <div style="margin-top: 10px;">Gambar tidak tersedia</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Tombol Tutup Modal -->
<div class="btnArea" style="text-align: right; margin-top: 25px;">
    <button type="button" class="btn red" onclick="window.parent.document.getElementById('closeModal').click()" style="padding: 10px 25px; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; background: transparent; color: #c02c2c;">Tutup</button>
</div>

<style>
    .raise { font-size: 0.7em; vertical-align: super; }
</style>