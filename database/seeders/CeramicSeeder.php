<?php

namespace Database\Seeders;

use App\Models\Ceramic;
use Illuminate\Database\Seeder;

class CeramicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data Mentah (Hanya data inti, sisanya dihitung otomatis)
        $rawCeramics = [
            [
                'type' => 'Lantai', // Lebih spesifik dari 'Biasa'
                'brand' => 'Roman',
                'sub_brand' => 'Ceramics',
                'code' => 'DRG/54-234C',
                'color' => 'Cassablanca Beige',
                'form' => 'Persegi', // Lebih deskriptif dari 'Lbr'
                'dimension_length' => 30.0, // cm
                'dimension_width' => 30.0,  // cm
                'dimension_thickness' => 0.8, // cm (8mm)
                'packaging' => 'Dus',
                'pieces_per_package' => 11,
                'store' => 'TB. Harapan Kita',
                'address' => 'BSD, Tangerang Selatan',
                'price_per_package' => 60000,
            ],
            [
                'type' => 'Lantai',
                'brand' => 'Platinum',
                'sub_brand' => 'Basic',
                'code' => 'CDF-101',
                'color' => 'Grey Corak',
                'form' => 'Persegi',
                'dimension_length' => 40.0,
                'dimension_width' => 40.0,
                'dimension_thickness' => 0.8,
                'packaging' => 'Dus',
                'pieces_per_package' => 6,
                'store' => 'TB Bakti Jaya',
                'address' => 'Gading Serpong, Tangerang',
                'price_per_package' => 67000,
            ],
            [
                'type' => 'Dinding', // Biasanya ukuran 20x25 untuk dinding
                'brand' => 'Asia Tile',
                'sub_brand' => 'Wall',
                'code' => 'AS-101',
                'color' => 'Putih Corak',
                'form' => 'Persegi Panjang',
                'dimension_length' => 20.0,
                'dimension_width' => 25.0,
                'dimension_thickness' => 0.7,
                'packaging' => 'Dus',
                'pieces_per_package' => 20,
                'store' => 'Maju Bersama',
                'address' => 'BSD, Tangerang Selatan',
                'price_per_package' => 59000,
            ],
            [
                'type' => 'Lantai',
                'brand' => 'Mulia',
                'sub_brand' => 'Signature',
                'code' => 'MU-101',
                'color' => 'Cream Polos',
                'form' => 'Persegi',
                'dimension_length' => 60.0,
                'dimension_width' => 60.0,
                'dimension_thickness' => 0.9,
                'packaging' => 'Dus',
                'pieces_per_package' => 4,
                'store' => 'TB Murah Sukses',
                'address' => 'Gading Serpong, Tangerang',
                'price_per_package' => 95000, // Harga 60x60 biasanya lebih mahal
            ],
            [
                'type' => 'Granit (HT)',
                'brand' => 'Indogress',
                'sub_brand' => 'Polished',
                'code' => 'Ig-101',
                'color' => 'Salt Pepper',
                'form' => 'Persegi',
                'dimension_length' => 60.0,
                'dimension_width' => 60.0,
                'dimension_thickness' => 1.0, // 10mm
                'packaging' => 'Dus',
                'pieces_per_package' => 4,
                'store' => 'TB Bakti Jaya',
                'address' => 'Gading Serpong, Tangerang',
                'price_per_package' => 180000, // Perbaiki harga yang lebih realistis
            ],
            [
                'type' => 'Granit (HT)',
                'brand' => 'Valentino Gress',
                'sub_brand' => 'Glazed',
                'code' => 'VG-101',
                'color' => 'Carrara White',
                'form' => 'Persegi',
                'dimension_length' => 60.0,
                'dimension_width' => 60.0,
                'dimension_thickness' => 1.0,
                'packaging' => 'Dus',
                'pieces_per_package' => 4, // Biasanya 60x60 isi 4 (1.44m2)
                'store' => 'TB Bakti Jaya',
                'address' => 'Gading Serpong, Tangerang',
                'price_per_package' => 220000,
            ],
        ];

        foreach ($rawCeramics as $data) {
            // Kalkulasi Otomatis
            // 1. Luas per keping (m2) = (P/100) * (L/100)
            $areaPerPiece = ($data['dimension_length'] / 100) * ($data['dimension_width'] / 100);

            // 2. Coverage per dus (m2) = Luas per keping * Isi per dus
            $coveragePerPackage = $areaPerPiece * $data['pieces_per_package'];
            // Rounding ke 4 desimal agar rapi di DB
            $coveragePerPackage = round($coveragePerPackage, 4);

            // 3. Harga Komparasi (Rp/m2) = Harga per dus / Coverage per dus
            $comparisonPrice = 0;
            if ($coveragePerPackage > 0) {
                $comparisonPrice = $data['price_per_package'] / $coveragePerPackage;
            }
            // Rounding harga
            $comparisonPrice = round($comparisonPrice, 2);

            // Gunakan updateOrCreate agar tidak duplikat jika dijalankan ulang
            // Kunci unik: Brand + Code
            Ceramic::updateOrCreate(
                [
                    'brand' => $data['brand'],
                    'code' => $data['code'],
                ],
                [
                    'material_name' => 'Keramik',
                    'type' => $data['type'],
                    'sub_brand' => $data['sub_brand'],
                    'color' => $data['color'],
                    'form' => $data['form'],
                    'dimension_length' => $data['dimension_length'],
                    'dimension_width' => $data['dimension_width'],
                    'dimension_thickness' => $data['dimension_thickness'],
                    'packaging' => $data['packaging'],
                    'pieces_per_package' => $data['pieces_per_package'],
                    'coverage_per_package' => $coveragePerPackage,
                    'store' => $data['store'],
                    'address' => $data['address'],
                    'price_per_package' => $data['price_per_package'],
                    'comparison_price_per_m2' => $comparisonPrice,
                ]
            );
        }

        $this->command->info('Ceramics table seeded successfully.');
        $this->command->info('Total ceramics: ' . Ceramic::count());
    }
}