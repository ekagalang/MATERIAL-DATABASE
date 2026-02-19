<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Cat;

/**
 * Formula - Perhitungan Cat Dinding
 * Menghitung kebutuhan material untuk pengecatan dinding
 */
class PaintingFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'painting';
    }

    public static function getName(): string
    {
        return 'Pengecatan Dinding Saja';
    }

    public static function getDescription(): string
    {
        return 'Menghitung Pengecatan Dinding dengan input panjang, tinggi, dan jumlah lapis.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['cat'];
    }

    public function validate(array $params): bool
    {
        // Check wall dimensions and layer count
        if (!isset($params['wall_length']) || $params['wall_length'] <= 0) {
            return false;
        }

        if (!isset($params['wall_height']) || $params['wall_height'] <= 0) {
            return false;
        }

        if (!isset($params['layer_count']) || $params['layer_count'] <= 0) {
            return false;
        }

        // cat_id is optional - will use default if not provided

        return true;
    }

    public function calculate(array $params): array
    {
        $trace = $this->trace($params);
        return $trace['final_result'];
    }

    public function trace(array $params): array
    {
        $trace = [];
        $trace['mode'] = self::getName();
        $trace['steps'] = [];
        $n = static fn($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjang = $n($params['wall_length']); // m
        $tinggi = $n($params['wall_height']); // m
        $jumlahLapis = $n($params['layer_count']); // jumlah lapis pengecatan

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $panjang . ' m',
                'Tinggi Dinding' => $tinggi . ' m',
                'Jumlah Lapis' => $jumlahLapis,
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        // Use provided cat_id or get default cat
        if (!empty($params['cat_id'])) {
            $cat = Cat::findOrFail($params['cat_id']);
        } else {
            $cat = Cat::orderBy('id')->first();
            if (!$cat) {
                throw new \Exception('Tidak ada data cat di database. Silakan tambahkan data cat terlebih dahulu.');
            }
        }

        $beratBersihCat = $n($cat->package_weight_net); // kg
        $coverageRate = $n(7.5); // M2 per kg per lapis
        $ratioAir = $n(0.05); // 5% dari berat bersih cat

        if ($beratBersihCat <= 0) {
            throw new \RuntimeException(
                'Data cat tidak lengkap untuk kalkulasi: berat bersih kemasan (package_weight_net) harus lebih dari 0.',
            );
        }

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Cat' => $cat->brand . ' - ' . $cat->cat_name,
                'Berat Bersih Cat' => $beratBersihCat . ' kg',
                'Coverage Rate' => $coverageRate . ' M2/kg/lapis',
                'Rasio Campuran' => '1 : 5% (Cat : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        $luasBidang = $n($panjang * $tinggi);

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang',
            'formula' => 'Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$panjang × $tinggi",
                'Hasil' => NumberHelper::format($luasBidang) . ' M2',
            ],
        ];

        // ============ STEP 4: Hitung Volume Adukan Per Kemasan ============
        // Volume adukan per kemasan = Berat bersih cat + (berat bersih cat * 5%)
        $beratAirPerKemasan = $n($beratBersihCat * $ratioAir);
        $volumeAdukanPerKemasan = $n($beratBersihCat + $beratAirPerKemasan);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Volume Adukan Per Kemasan',
            'formula' => 'Berat bersih cat + (berat bersih cat × 5%)',
            'info' => 'Total berat cat + air per kemasan',
            'calculations' => [
                'Berat Cat' => NumberHelper::format($beratBersihCat) . ' kg',
                'Berat Air (5%)' => NumberHelper::format($beratAirPerKemasan) . ' kg',
                'Total Volume Adukan' => NumberHelper::format($volumeAdukanPerKemasan) . ' kg',
            ],
        ];

        // ============ STEP 5: Hitung Luas Pengecatan Per Lapis Per Kemasan ============
        // Luas pengecatan per lapis per kemasan = 7.5 M2 per kg per lapis * volume adukan per kemasan
        $luasPengecatanPerLapisPerKemasan = $n($coverageRate * $volumeAdukanPerKemasan);

        if ($luasPengecatanPerLapisPerKemasan <= 0) {
            throw new \RuntimeException(
                'Luas pengecatan per lapis tidak valid (0). Periksa data berat bersih cat dan parameter coverage.',
            );
        }

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Luas Pengecatan Per Lapis Per Kemasan',
            'formula' => '7.5 M2/kg/lapis × Volume adukan per kemasan',
            'info' => 'Berapa M2 yang bisa dicat dengan 1 kemasan untuk 1 lapis',
            'calculations' => [
                'Perhitungan' => "$coverageRate × " . NumberHelper::format($volumeAdukanPerKemasan),
                'Hasil' => NumberHelper::format($luasPengecatanPerLapisPerKemasan) . ' M2',
            ],
        ];

        // ============ STEP 6: Hitung Volume Adukan Per 1M2 ============
        // Volume adukan Per 1M2 = Volume adukan per kemasan / Luas pengecatan per lapis per kemasan
        $volumeAdukanPer1M2 = $n($volumeAdukanPerKemasan / $luasPengecatanPerLapisPerKemasan);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Volume Adukan Per 1M2',
            'formula' => 'Volume adukan per kemasan / Luas pengecatan per lapis per kemasan',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($volumeAdukanPerKemasan) .
                    ' / ' .
                    NumberHelper::format($luasPengecatanPerLapisPerKemasan),
                'Hasil' => NumberHelper::format($volumeAdukanPer1M2) . ' kg',
            ],
        ];

        // ============ STEP 7: Hitung Galon Per 1M2 ============
        // Galon per 1M2 = 1 / luas pengecatan per 1 lapis per kemasan
        $galonPer1M2 = $n(1 / $luasPengecatanPerLapisPerKemasan);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Galon (Kemasan) Per 1M2',
            'formula' => '1 / Luas pengecatan per lapis per kemasan',
            'calculations' => [
                'Perhitungan' => '1 / ' . NumberHelper::format($luasPengecatanPerLapisPerKemasan),
                'Hasil' => NumberHelper::format($galonPer1M2) . ' kemasan',
            ],
        ];

        // ============ STEP 8: Hitung Liter Per 1M2 (OPSIONAL) ============
        // Liter per 1M2 = Volume kemasan cat / luas pengecatan per 1 lapis per kemasan
        // Hanya dihitung jika cat memiliki data volume
        $volumeKemasan = $cat->volume ?? null;
        if ($volumeKemasan !== null) {
            $volumeKemasan = $n($volumeKemasan);
        }
        $literPer1M2 = null;

        if ($volumeKemasan && $volumeKemasan > 0) {
            $literPer1M2 = $n($volumeKemasan / $luasPengecatanPerLapisPerKemasan);

            $trace['steps'][] = [
                'step' => 8,
                'title' => 'Liter Cat Per 1M2 (dari data volume cat)',
                'formula' => 'Volume kemasan / Luas pengecatan per lapis per kemasan',
                'info' => 'Volume kemasan cat: ' . $volumeKemasan . ' ' . ($cat->volume_unit ?? 'liter'),
                'calculations' => [
                    'Perhitungan' =>
                        NumberHelper::format($volumeKemasan) .
                        ' / ' .
                        NumberHelper::format($luasPengecatanPerLapisPerKemasan),
                    'Hasil' => NumberHelper::format($literPer1M2) . ' liter',
                ],
            ];
        }

        // ============ STEP 9: Hitung Kg Per 1M2 ============
        // Kg per 1M2 = Berat bersih / luas pengecatan per 1 sisi per kemasan
        $kgCatPer1M2 = $n($beratBersihCat / $luasPengecatanPerLapisPerKemasan);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Kg Cat Per 1M2',
            'formula' => 'Berat bersih / Luas pengecatan per lapis per kemasan',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($beratBersihCat) .
                    ' / ' .
                    NumberHelper::format($luasPengecatanPerLapisPerKemasan),
                'Hasil' => NumberHelper::format($kgCatPer1M2) . ' kg',
            ],
        ];

        // ============ STEP 10: Hitung Liter Air Per 1M2 ============
        // Liter air per 1M2 = Ratio air (5%) / luas pengecatan per 1 sisi per kemasan
        $beratAir = $beratAirPerKemasan;
        $literAirPer1M2 = $n($beratAir / $luasPengecatanPerLapisPerKemasan);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Liter Air Per 1M2',
            'formula' => '(Berat bersih × 5%) / Luas pengecatan per lapis per kemasan',
            'calculations' => [
                'Berat Air' => NumberHelper::format($beratAir) . ' kg',
                'Perhitungan' =>
                    NumberHelper::format($beratAir) . ' / ' . NumberHelper::format($luasPengecatanPerLapisPerKemasan),
                'Hasil' => NumberHelper::format($literAirPer1M2) . ' liter',
            ],
        ];

        // ============ STEP 11: Hitung Volume Adukan Cat Per Pekerjaan (1 Lapis) ============
        // Volume adukan Cat per pekerjaan = Volume adukan Per 1M2 * Luas Bidang
        $volumeAdukanPerPekerjaanPerLapis = $n($volumeAdukanPer1M2 * $luasBidang);

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Volume Adukan Cat Per Pekerjaan (1 Lapis)',
            'formula' => 'Volume adukan Per 1M2 × Luas Bidang',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($volumeAdukanPer1M2) . ' × ' . NumberHelper::format($luasBidang),
                'Hasil' => NumberHelper::format($volumeAdukanPerPekerjaanPerLapis) . ' kg',
            ],
        ];

        // ============ STEP 12: Hitung Galon Per Pekerjaan (1 Lapis) ============
        // Galon per pekerjaan = Galon per 1M2 * Luas bidang
        $galonPerPekerjaanPerLapis = $n($galonPer1M2 * $luasBidang);

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Galon (Kemasan) Per Pekerjaan (1 Lapis)',
            'formula' => 'Galon per 1M2 × Luas bidang',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($galonPer1M2) . ' × ' . NumberHelper::format($luasBidang),
                'Hasil' => NumberHelper::format($galonPerPekerjaanPerLapis) . ' kemasan',
            ],
        ];

        // ============ STEP 13: Hitung Liter Per Pekerjaan (1 Lapis) - OPSIONAL ============
        // Liter per pekerjaan = Liter per 1M2 * Luas bidang
        $literCatPerPekerjaanPerLapis = null;

        if ($literPer1M2 !== null) {
            $literCatPerPekerjaanPerLapis = $n($literPer1M2 * $luasBidang);

            $trace['steps'][] = [
                'step' => 13,
                'title' => 'Liter Cat Per Pekerjaan (1 Lapis)',
                'formula' => 'Liter per 1M2 × Luas bidang',
                'calculations' => [
                    'Perhitungan' => NumberHelper::format($literPer1M2) . ' × ' . NumberHelper::format($luasBidang),
                    'Hasil' => NumberHelper::format($literCatPerPekerjaanPerLapis) . ' liter',
                ],
            ];
        }

        // ============ STEP 14: Hitung Kg Per Pekerjaan (1 Lapis) ============
        // Kg per pekerjaan = Kg per 1M2 * luas bidang
        $kgCatPerPekerjaanPerLapis = $n($beratBersihCat * $galonPerPekerjaanPerLapis);

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Kg Cat Per Pekerjaan (1 Lapis)',
            'formula' => 'Kg per 1M2 × Luas bidang',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($beratBersihCat) . ' × ' . NumberHelper::format($galonPerPekerjaanPerLapis),
                'Hasil' => NumberHelper::format($kgCatPerPekerjaanPerLapis) . ' kg',
            ],
        ];

        // ============ STEP 15: Hitung Liter Air Per Pekerjaan (1 Lapis) ============
        // Liter air per pekerjaan = Liter air per 1M2 * Luas bidang
        $literAirPerPekerjaanPerLapis = $n($literAirPer1M2 * $luasBidang);

        $trace['steps'][] = [
            'step' => 15,
            'title' => 'Liter Air Per Pekerjaan (1 Lapis)',
            'formula' => 'Liter air per 1M2 × Luas bidang',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($literAirPer1M2) . ' × ' . NumberHelper::format($luasBidang),
                'Hasil' => NumberHelper::format($literAirPerPekerjaanPerLapis) . ' liter',
            ],
        ];

        // ============ STEP 16: Kalikan dengan Jumlah Lapis ============
        // Hasil akhir * banyak lapis
        $volumeAdukanPekerjaan = $n($volumeAdukanPerPekerjaanPerLapis * $jumlahLapis);
        $kemasanPekerjaan = $n($galonPerPekerjaanPerLapis * $jumlahLapis);
        $literCatPekerjaan =
            $literCatPerPekerjaanPerLapis !== null ? $n($literCatPerPekerjaanPerLapis * $jumlahLapis) : null;
        $kgCatPekerjaan = $n($kgCatPerPekerjaanPerLapis * $jumlahLapis);
        $literAirPekerjaan = $n($literAirPerPekerjaanPerLapis * $jumlahLapis);

        $grandLuasBidang = $n($luasBidang * $jumlahLapis);

        $calculations = [
            'Volume Adukan Total' => NumberHelper::format($volumeAdukanPekerjaan) . ' kg',
            'Kemasan' => NumberHelper::format($kemasanPekerjaan) . ' galon',
            'Kg Cat' => NumberHelper::format($kgCatPekerjaan) . ' kg',
            'Liter Air' => NumberHelper::format($literAirPekerjaan) . ' liter',
        ];

        // Tambahkan Liter Cat hanya jika ada datanya
        if ($literCatPekerjaan !== null) {
            $calculations['Liter Cat'] = NumberHelper::format($literCatPekerjaan) . ' liter';
        }

        $trace['steps'][] = [
            'step' => 16,
            'title' => 'Total Kebutuhan Material (Semua Lapis)',
            'formula' => 'Hasil per lapis × Jumlah lapis',
            'info' =>
                'Total ' .
                $jumlahLapis .
                ' lapis × ' .
                NumberHelper::format($luasBidang) .
                ' M2 = ' .
                $grandLuasBidang .
                ' M2',
            'calculations' => $calculations,
        ];

        // ============ STEP 17: Hitung Harga ============
        $catPrice = $n($cat->purchase_price ?? 0, 0); // Harga per kemasan
        $totalCatPrice = $n($kemasanPekerjaan * $catPrice, 0);
        $grandTotal = $n($totalCatPrice, 0); // Hanya cat, tanpa material lain

        $trace['steps'][] = [
            'step' => 17,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Cat per Kemasan' => NumberHelper::currency($catPrice),
                'Total Harga Cat' => NumberHelper::currency($totalCatPrice),
                'Grand Total' => NumberHelper::currency($grandTotal),
            ],
        ];

        // ============ Final Result ============
        $trace['final_result'] = [
            'total_bricks' => 0, // Not applicable for painting
            'brick_price_per_piece' => 0,
            'total_brick_price' => 0,

            // Cat materials
            'cat_packages' => $kemasanPekerjaan,
            'cat_kg' => $kgCatPekerjaan,
            'cat_liters' => $literCatPekerjaan, // Bisa null jika cat tidak punya data volume
            'water_liters' => $literAirPekerjaan,
            'paint_volume_kg' => $volumeAdukanPekerjaan,

            // No cement or sand
            'cement_sak' => 0,
            'cement_kg' => 0,
            'cement_m3' => 0,
            'sand_m3' => 0,
            'sand_sak' => 0,
            'water_m3' => $n($literAirPekerjaan / 1000),
            'mortar_volume_m3' => 0,

            // Prices
            'cat_price_per_package' => $catPrice,
            'total_cat_price' => $totalCatPrice,
            'cement_price_per_sak' => 0,
            'total_cement_price' => 0,
            'sand_price_per_m3' => 0,
            'total_sand_price' => 0,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $luasBidang,
            'layer_count' => $jumlahLapis,
            'coverage_rate' => $coverageRate,
            'has_volume_data' => $volumeKemasan !== null && $volumeKemasan > 0,
        ];

        return $trace;
    }
}
