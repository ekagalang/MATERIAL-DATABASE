<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;
use App\Models\Cat;
use App\Models\Ceramic;

class MassDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Definisikan 5 Toko (Nama & Alamat)
        $stores = [
            ['name' => 'TB. Sinar Jaya Abadi', 'address' => 'Jl. Raya Serpong No. 12, Tangerang Selatan'],
            ['name' => 'Depo Bangunan Sejahtera', 'address' => 'Jl. Fatmawati Raya No. 88, Jakarta Selatan'],
            ['name' => 'Mitra10 Express BSD', 'address' => 'BSD City Sektor 1.3, Tangerang'],
            ['name' => 'TB. Maju Makmur', 'address' => 'Jl. Pajajaran No. 5, Bogor'],
            ['name' => 'Supermarket Bangunan Kokoh', 'address' => 'Jl. Daan Mogot KM 14, Jakarta Barat'],
        ];

        // 2. Blueprint Data yang Lebih Kaya & Lengkap
        
        // --- BRICK (Bata) - 10 Variasi ---
        $brickBlueprints = [
            ['type' => 'Bata Merah', 'brand' => 'Lokal Jumbo', 'form' => 'Balok', 'p' => 20, 'l' => 10, 't' => 5, 'base_price' => 800, 'vol' => 0.001],
            ['type' => 'Bata Merah', 'brand' => 'Cikarang Super', 'form' => 'Balok Halus', 'p' => 19, 'l' => 9, 't' => 4.5, 'base_price' => 750, 'vol' => 0.00077],
            ['type' => 'Bata Merah', 'brand' => 'Garut Press', 'form' => 'Press Mesin', 'p' => 21, 'l' => 10, 't' => 5.5, 'base_price' => 900, 'vol' => 0.0011],
            ['type' => 'Bata Ringan', 'brand' => 'Citicon', 'form' => 'Hebel Standard', 'p' => 60, 'l' => 20, 't' => 10, 'base_price' => 11000, 'vol' => 0.012],
            ['type' => 'Bata Ringan', 'brand' => 'Citicon', 'form' => 'Hebel Tipis', 'p' => 60, 'l' => 20, 't' => 7.5, 'base_price' => 8800, 'vol' => 0.009],
            ['type' => 'Bata Ringan', 'brand' => 'Grand Elephant', 'form' => 'Hebel Premium', 'p' => 60, 'l' => 20, 't' => 10, 'base_price' => 11500, 'vol' => 0.012],
            ['type' => 'Bata Ringan', 'brand' => 'Blesscon', 'form' => 'Hebel AAC', 'p' => 60, 'l' => 20, 't' => 10, 'base_price' => 10800, 'vol' => 0.012],
            ['type' => 'Batako', 'brand' => 'Lokal Press', 'form' => '3 Lubang', 'p' => 40, 'l' => 20, 't' => 10, 'base_price' => 3500, 'vol' => 0.008],
            ['type' => 'Batako', 'brand' => 'Lokal Semen', 'form' => 'Buntu/Pejal', 'p' => 38, 'l' => 18, 't' => 8, 'base_price' => 4000, 'vol' => 0.0055],
            ['type' => 'Roster', 'brand' => 'Mulia', 'form' => 'Minimalis', 'p' => 20, 'l' => 20, 't' => 10, 'base_price' => 15000, 'vol' => 0.004],
        ];

        // --- CEMENT (Semen Biasa) - 8 Variasi ---
        $cementBlueprints = [
            ['brand' => 'Tiga Roda', 'sub' => 'PCC', 'code' => 'PCC-50', 'color' => 'Grey', 'unit' => 'Sak', 'weight' => 50, 'base_price' => 65000],
            ['brand' => 'Tiga Roda', 'sub' => 'PCC', 'code' => 'PCC-40', 'color' => 'Grey', 'unit' => 'Sak', 'weight' => 40, 'base_price' => 54000],
            ['brand' => 'Semen Gresik', 'sub' => 'PPC', 'code' => 'SG-40', 'color' => 'Dark Grey', 'unit' => 'Sak', 'weight' => 40, 'base_price' => 52000],
            ['brand' => 'Semen Gresik', 'sub' => 'PPC', 'code' => 'SG-50', 'color' => 'Dark Grey', 'unit' => 'Sak', 'weight' => 50, 'base_price' => 64000],
            ['brand' => 'Holcim', 'sub' => 'Dynamix', 'code' => 'Serbaguna', 'color' => 'Light Grey', 'unit' => 'Sak', 'weight' => 40, 'base_price' => 53000],
            ['brand' => 'Holcim', 'sub' => 'Dynamix', 'code' => 'Extra Kuat', 'color' => 'Grey', 'unit' => 'Sak', 'weight' => 50, 'base_price' => 66000],
            ['brand' => 'Semen Padang', 'sub' => 'PCC', 'code' => 'SP-50', 'color' => 'Grey', 'unit' => 'Sak', 'weight' => 50, 'base_price' => 63000],
            ['brand' => 'Semen Jakarta', 'sub' => 'Eco', 'code' => 'SJ-40', 'color' => 'Grey', 'unit' => 'Sak', 'weight' => 40, 'base_price' => 48000],
        ];

        // --- NAT (Semen Type Nat) - 12 Variasi ---
        $natBlueprints = [
            ['brand' => 'AM', 'sub' => '51', 'color' => 'White', 'code' => '001', 'base_price' => 32000],
            ['brand' => 'AM', 'sub' => '51', 'color' => 'Grey', 'code' => '002', 'base_price' => 32000],
            ['brand' => 'AM', 'sub' => '51', 'color' => 'Black', 'code' => '003', 'base_price' => 32000],
            ['brand' => 'AM', 'sub' => '51', 'color' => 'Cream', 'code' => '004', 'base_price' => 32000],
            ['brand' => 'AM', 'sub' => '53', 'color' => 'Super White', 'code' => 'SW-1', 'base_price' => 35000],
            ['brand' => 'MU', 'sub' => '408', 'color' => 'Cocoa', 'code' => 'MU-C1', 'base_price' => 28000],
            ['brand' => 'MU', 'sub' => '408', 'color' => 'Beige', 'code' => 'MU-C2', 'base_price' => 28000],
            ['brand' => 'MU', 'sub' => '408', 'color' => 'Dark Grey', 'code' => 'MU-G1', 'base_price' => 28000],
            ['brand' => 'Lemkra', 'sub' => 'FK 101', 'color' => 'Silver', 'code' => 'L-S1', 'base_price' => 35000],
            ['brand' => 'Lemkra', 'sub' => 'FK 101', 'color' => 'Gold', 'code' => 'L-G1', 'base_price' => 38000],
            ['brand' => 'Sika', 'sub' => 'Tile Grout', 'color' => 'White', 'code' => 'S-W', 'base_price' => 30000],
            ['brand' => 'Sika', 'sub' => 'Tile Grout', 'color' => 'Grey', 'code' => 'S-G', 'base_price' => 30000],
        ];

        // --- SAND (Pasir) - 6 Variasi ---
        $sandBlueprints = [
            ['type' => 'Pasir Bangka', 'brand' => 'Putih Super', 'unit' => 'Kijang', 'vol' => 0.8, 'base_price' => 350000],
            ['type' => 'Pasir Bangka', 'brand' => 'Putih Super', 'unit' => 'Truk (6m3)', 'vol' => 6, 'base_price' => 2400000],
            ['type' => 'Pasir Mundu', 'brand' => 'Coklat Halus', 'unit' => 'Kijang', 'vol' => 0.8, 'base_price' => 280000],
            ['type' => 'Pasir Mundu', 'brand' => 'Coklat Halus', 'unit' => 'Truk (6m3)', 'vol' => 6, 'base_price' => 1900000],
            ['type' => 'Pasir Cileungsi', 'brand' => 'Hitam Kasar', 'unit' => 'Kijang', 'vol' => 0.8, 'base_price' => 250000],
            ['type' => 'Pasir Lampung', 'brand' => 'Halus', 'unit' => 'Karung (50kg)', 'vol' => 0.035, 'base_price' => 25000],
        ];

        // --- CAT (Paint) - 15 Variasi ---
        $catBlueprints = [
            ['brand' => 'Dulux', 'sub' => 'Catylac', 'type' => 'Interior', 'color' => 'Putih (White)', 'code' => '40104', 'unit' => 'Pail', 'vol' => 25, 'weight' => 25, 'base_price' => 750000],
            ['brand' => 'Dulux', 'sub' => 'Catylac', 'type' => 'Interior', 'color' => 'Icy Rose', 'code' => '42345', 'unit' => 'Galon', 'vol' => 5, 'weight' => 5, 'base_price' => 160000],
            ['brand' => 'Dulux', 'sub' => 'Pentalite', 'type' => 'Interior', 'color' => 'Morning Dew', 'code' => '55555', 'unit' => 'Galon', 'vol' => 2.5, 'weight' => 3.5, 'base_price' => 180000],
            ['brand' => 'Nippon Paint', 'sub' => 'Vinilex', 'type' => 'Interior', 'color' => 'Super White', 'code' => '300', 'unit' => 'Pail', 'vol' => 20, 'weight' => 20, 'base_price' => 680000],
            ['brand' => 'Nippon Paint', 'sub' => 'Vinilex', 'type' => 'Interior', 'color' => 'Metrolite', 'code' => '900', 'unit' => 'Galon', 'vol' => 5, 'weight' => 5, 'base_price' => 145000],
            ['brand' => 'Nippon Paint', 'sub' => 'Spot-less', 'type' => 'Interior', 'color' => 'Lily White', 'code' => '1001', 'unit' => 'Galon', 'vol' => 2.5, 'weight' => 3.5, 'base_price' => 210000],
            ['brand' => 'Avian', 'sub' => 'Avitex', 'type' => 'Exterior', 'color' => 'Abu-abu', 'code' => 'EX-01', 'unit' => 'Galon', 'vol' => 5, 'weight' => 5, 'base_price' => 140000],
            ['brand' => 'Avian', 'sub' => 'Avitex', 'type' => 'Interior', 'color' => 'Putih Salju', 'code' => 'SW', 'unit' => 'Pail', 'vol' => 20, 'weight' => 20, 'base_price' => 580000],
            ['brand' => 'Jotun', 'sub' => 'Jotaplast', 'type' => 'Interior', 'color' => 'Majestic White', 'code' => '001', 'unit' => 'Pail', 'vol' => 20, 'weight' => 20, 'base_price' => 850000],
            ['brand' => 'Jotun', 'sub' => 'Majestic', 'type' => 'Interior', 'color' => 'Timeless', 'code' => '1024', 'unit' => 'Galon', 'vol' => 2.5, 'weight' => 3.5, 'base_price' => 250000],
            ['brand' => 'Jotun', 'sub' => 'Tough Shield', 'type' => 'Exterior', 'color' => 'Classic White', 'code' => '9918', 'unit' => 'Pail', 'vol' => 20, 'weight' => 20, 'base_price' => 1200000],
            ['brand' => 'No Drop', 'sub' => 'Waterproof', 'type' => 'Exterior', 'color' => 'Abu Muda', 'code' => '010', 'unit' => 'Galon', 'vol' => 4, 'weight' => 4, 'base_price' => 195000],
            ['brand' => 'No Drop', 'sub' => 'Waterproof', 'type' => 'Exterior', 'color' => 'Transparan', 'code' => '001', 'unit' => 'Galon', 'vol' => 4, 'weight' => 4, 'base_price' => 210000],
            ['brand' => 'Aquaproof', 'sub' => 'Standard', 'type' => 'Exterior', 'color' => 'Merah', 'code' => '045', 'unit' => 'Galon', 'vol' => 4, 'weight' => 4, 'base_price' => 200000],
            ['brand' => 'Mowilex', 'sub' => 'Emulsion', 'type' => 'Interior', 'color' => 'Putih', 'code' => 'E-100', 'unit' => 'Pail', 'vol' => 20, 'weight' => 20, 'base_price' => 950000],
        ];

        // --- CERAMIC (Keramik) - 15 Variasi ---
        $ceramicBlueprints = [
            ['brand' => 'Roman', 'sub' => 'dModesto', 'code' => 'G334', 'size' => 30, 'thick' => 0.8, 'surf' => 'Matt', 'pcs' => 11, 'm2' => 0.99, 'base_price' => 110000],
            ['brand' => 'Roman', 'sub' => 'dCatania', 'code' => 'W445', 'size' => 40, 'thick' => 0.9, 'surf' => 'Glossy', 'pcs' => 6, 'm2' => 0.96, 'base_price' => 125000],
            ['brand' => 'Roman', 'sub' => 'Interlok', 'code' => 'dLimboto', 'size' => 30, 'thick' => 0.8, 'surf' => 'Textured', 'pcs' => 11, 'm2' => 0.99, 'base_price' => 115000],
            ['brand' => 'Mulia', 'sub' => 'Signature', 'code' => 'Beige Polos', 'size' => 40, 'thick' => 0.8, 'surf' => 'Matt', 'pcs' => 6, 'm2' => 0.96, 'base_price' => 65000],
            ['brand' => 'Mulia', 'sub' => 'Accura', 'code' => 'White Marble', 'size' => 40, 'thick' => 0.8, 'surf' => 'Glossy', 'pcs' => 6, 'm2' => 0.96, 'base_price' => 68000],
            ['brand' => 'Platinum', 'sub' => 'Alaska', 'code' => 'Basic White', 'size' => 60, 'thick' => 1.0, 'surf' => 'Glossy', 'pcs' => 4, 'm2' => 1.44, 'base_price' => 140000],
            ['brand' => 'Platinum', 'sub' => 'Amazon', 'code' => 'Brown Wood', 'size' => 60, 'thick' => 1.0, 'surf' => 'Matt', 'pcs' => 4, 'm2' => 1.44, 'base_price' => 155000],
            ['brand' => 'Asia Tile', 'sub' => 'Oscar', 'code' => 'Grey', 'size' => 30, 'thick' => 0.7, 'surf' => 'Textured', 'pcs' => 11, 'm2' => 0.99, 'base_price' => 55000],
            ['brand' => 'Asia Tile', 'sub' => 'Alpha', 'code' => 'White', 'size' => 30, 'thick' => 0.7, 'surf' => 'Glossy', 'pcs' => 11, 'm2' => 0.99, 'base_price' => 52000],
            ['brand' => 'Milan', 'sub' => 'Habitat', 'code' => 'Cream', 'size' => 50, 'thick' => 0.9, 'surf' => 'Glossy', 'pcs' => 4, 'm2' => 1.0, 'base_price' => 95000],
            ['brand' => 'Milan', 'sub' => 'Centro', 'code' => 'Dark Grey', 'size' => 50, 'thick' => 0.9, 'surf' => 'Matt', 'pcs' => 4, 'm2' => 1.0, 'base_price' => 98000],
            ['brand' => 'Arwana', 'sub' => 'Uno', 'code' => 'Blue', 'size' => 20, 'thick' => 0.7, 'surf' => 'Matt', 'pcs' => 25, 'm2' => 1.0, 'base_price' => 60000],
            ['brand' => 'Arwana', 'sub' => 'Citra', 'code' => 'Pink', 'size' => 20, 'thick' => 0.7, 'surf' => 'Matt', 'pcs' => 25, 'm2' => 1.0, 'base_price' => 60000],
            ['brand' => 'Kia', 'sub' => 'Spectrum', 'code' => 'Green', 'size' => 30, 'thick' => 0.8, 'surf' => 'Glossy', 'pcs' => 11, 'm2' => 0.99, 'base_price' => 62000],
            ['brand' => 'Kia', 'sub' => 'Terra', 'code' => 'Terracotta', 'size' => 40, 'thick' => 0.8, 'surf' => 'Textured', 'pcs' => 6, 'm2' => 0.96, 'base_price' => 70000],
        ];

        $this->command->info('🚀 Memulai seeding massal untuk 5 toko dengan data LENGKAP...');

        // 3. Loop Toko dan Insert Data
        foreach ($stores as $storeIndex => $store) {
            $this->command->info("  🏪 Seeding toko: {$store['name']}...");

            // --- SEED BRICKS ---
            foreach ($brickBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                
                Brick::create([
                    'type' => $item['type'],
                    'material_name' => "{$item['type']} {$item['brand']}",
                    'brand' => $item['brand'],
                    'form' => $item['form'],
                    'dimension_length' => $item['p'],
                    'dimension_width' => $item['l'],
                    'dimension_height' => $item['t'],
                    'package_volume' => $item['vol'],
                    'price_per_piece' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_m3' => ($item['vol'] > 0) ? ($price / $item['vol']) : 0,
                ]);
            }

            // --- SEED CEMENTS ---
            foreach ($cementBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                
                Cement::create([
                    'type' => 'Semen',
                    'cement_name' => "Semen {$item['brand']} {$item['code']}",
                    'brand' => $item['brand'],
                    'sub_brand' => $item['sub'],
                    'code' => $item['code'],
                    'color' => $item['color'],
                    'package_unit' => $item['unit'],
                    'package_weight_gross' => $item['weight'],
                    'package_weight_net' => $item['weight'],
                    'package_price' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_kg' => $price / $item['weight'],
                ]);
            }

            // --- SEED NATS ---
            foreach ($natBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                $weight = 1.0; 

                Cement::create([
                    'type' => 'Nat', 
                    'cement_name' => "Nat {$item['brand']} {$item['color']}",
                    'brand' => $item['brand'],
                    'sub_brand' => $item['sub'],
                    'code' => $item['code'],
                    'color' => $item['color'],
                    'package_unit' => 'Bks',
                    'package_weight_gross' => $weight,
                    'package_weight_net' => $weight,
                    'package_price' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_kg' => $price / $weight,
                ]);
            }

            // --- SEED SANDS ---
            foreach ($sandBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                
                Sand::create([
                    'sand_name' => $item['type'] . ' ' . $item['brand'],
                    'type' => 'Pasir',
                    'brand' => $item['brand'],
                    'package_unit' => $item['unit'],
                    'package_volume' => $item['vol'],
                    'package_price' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_m3' => $price / $item['vol'],
                ]);
            }

            // --- SEED CATS ---
            foreach ($catBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                
                Cat::create([
                    'cat_name' => "Cat {$item['brand']} {$item['color']}",
                    'type' => $item['type'],
                    'brand' => $item['brand'],
                    'sub_brand' => $item['sub'],
                    'color_name' => $item['color'],
                    'color_code' => $item['code'],
                    'package_unit' => $item['unit'],
                    'volume' => $item['vol'],
                    'package_weight_net' => $item['weight'],
                    'purchase_price' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_kg' => $price / $item['weight'],
                ]);
            }

            // --- SEED CERAMICS ---
            foreach ($ceramicBlueprints as $item) {
                $price = $this->randomizePrice($item['base_price']);
                
                Ceramic::create([
                    'material_name' => "Keramik {$item['brand']} {$item['code']}",
                    'type' => 'Lantai',
                    'brand' => $item['brand'],
                    'sub_brand' => $item['sub'],
                    'code' => $item['code'],
                    'color' => $item['code'], // Use code as color if specific color not avail
                    'surface' => $item['surf'],
                    'form' => 'Persegi',
                    'dimension_length' => $item['size'],
                    'dimension_width' => $item['size'],
                    'dimension_thickness' => $item['thick'],
                    'packaging' => 'Dus',
                    'pieces_per_package' => $item['pcs'],
                    'coverage_per_package' => $item['m2'],
                    'price_per_package' => $price,
                    'store' => $store['name'],
                    'address' => $store['address'],
                    'comparison_price_per_m2' => $price / $item['m2'],
                ]);
            }
        }
        
        $this->command->info('✅ Mass seeding complete!');
    }

    private function randomizePrice($base)
    {
        $min = $base * 0.9;
        $max = $base * 1.1;
        return round(rand($min, $max) / 500) * 500;
    }
}