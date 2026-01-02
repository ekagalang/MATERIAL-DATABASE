<div class="card" style="box-shadow: none; border: none; background: transparent;">
    <div style="display: flex; gap: 32px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 300px;">
            <div style="background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%); border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; font-size: 13.5px; border-collapse: collapse;">
                    @php
                        $rows = [
                            ['label' => 'Material', 'value' => 'Keramik', 'color' => '#334155'],
                            ['label' => 'Merek', 'value' => $ceramic->brand, 'color' => '#0f172a', 'weight' => '700'],
                            ['label' => 'Sub Merek', 'value' => $ceramic->sub_brand ?? '-', 'color' => '#64748b'],
                            ['label' => 'Kode', 'value' => $ceramic->code ?? '-', 'color' => '#64748b'],
                            ['label' => 'Jenis', 'value' => $ceramic->type, 'color' => '#4f46e5', 'weight' => '600'],
                            ['label' => 'Warna', 'value' => $ceramic->color, 'color' => '#0f172a'],
                            ['label' => 'Bentuk', 'value' => $ceramic->form, 'color' => '#0f172a'],
                        ];
                    @endphp

                    @foreach($rows as $index => $row)
                    <tr style="border-bottom: 1px solid #f1f5f9; {{ $index === 0 ? 'background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);' : '' }}">
                        <td style="padding: 14px 20px; width: 35%; color: #64748b; font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">{{ $row['label'] }}</td>
                        <td style="padding: 14px 20px; width: 65%; color: {{ $row['color'] }}; font-weight: {{ $row['weight'] ?? '500' }};">{{ $row['value'] }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>

            <div style="margin-top: 24px;">
                <h3 style="font-size: 12px; font-weight: 700; color: #94a3b8; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">Spesifikasi Teknis</h3>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div>
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Dimensi</div>
                            <div style="font-size: 16px; font-weight: 600; color: #0f172a;">{{ $ceramic->dimension_length }} x {{ $ceramic->dimension_width }} cm</div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Tebal: {{ $ceramic->dimension_thickness }} mm</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; text-transform: uppercase;">Packaging</div>
                            <div style="font-size: 16px; font-weight: 600; color: #0f172a;">{{ $ceramic->pieces_per_package }} Pcs / {{ $ceramic->packaging }}</div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Coverage: {{ $ceramic->coverage_per_package }} m²</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <h3 style="font-size: 12px; font-weight: 700; color: #94a3b8; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">Analisa Harga</h3>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="color: #166534; font-weight: 500; font-size: 14px;">Harga per Dus</span>
                        <span style="font-size: 20px; font-weight: 700; color: #15803d;">Rp {{ number_format($ceramic->price_per_package, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed #bbf7d0;">
                        <span style="color: #166534; font-size: 13px;">Estimasi per m²</span>
                        <span style="font-size: 15px; font-weight: 600; color: #16a34a;">Rp {{ number_format($ceramic->comparison_price_per_m2, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="width: 320px; flex-shrink: 0;">
            <div style="background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%); border: 1px solid #e2e8f0; border-radius: 16px; padding: 8px;">
                <div style="border-radius: 12px; display: flex; align-items: center; justify-content: center; min-height: 320px; color: #cbd5e1; flex-direction: column; text-align: center; background: #f8fafc; overflow: hidden; position: relative;">
                    @if($ceramic->photo)
                        <img src="{{ Storage::url($ceramic->photo) }}" alt="Foto Produk" style="width: 100%; height: auto; max-height: 400px; object-fit: contain;">
                    @else
                        <div style="font-size: 64px; opacity: 0.4; margin-bottom: 16px;">📷</div>
                        <div style="font-size: 14px; font-weight: 600; color: #94a3b8;">Tidak ada foto</div>
                    @endif
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Lokasi Toko</div>
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0;">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div>
                        <div style="font-size: 15px; color: #0f172a; font-weight: 600;">{{ $ceramic->store ?? '-' }}</div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px; line-height: 1.5;">{{ $ceramic->address }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="display: flex; justify-content: flex-end; margin-top: 30px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
        <button type="button" 
                onclick="closeFloatingModal()"
                style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: #ffffff; padding: 12px 30px; border-radius: 10px; font-weight: 500; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); font-size: 14px;">
            Tutup
        </button>
    </div>
</div>