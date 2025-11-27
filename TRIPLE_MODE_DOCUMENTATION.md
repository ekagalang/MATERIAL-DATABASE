# Dokumentasi Triple Mode Calculator
## Sistem Perhitungan Bata dengan 3 Metode Berbeda

---

## Overview

Triple Mode Calculator adalah sistem perhitungan bata yang menyediakan **3 metode perhitungan berbeda** untuk memberikan fleksibilitas dalam estimasi material. Setiap mode dirancang untuk use case yang berbeda.

---

## Mode 1: PROFESSIONAL (Volume Mortar)

### Deskripsi
Mode ini menggunakan perhitungan **berbasis volume mortar aktual** dengan data empiris yang sudah terverifikasi. Ini adalah sistem yang sudah ada di aplikasi sebelumnya.

### Karakteristik
- ✅ **Paling Akurat** - Berbasis data empiris dari Excel dengan interpolasi linear
- ✅ **Paling Hemat** - Material paling efisien (110.95 kg semen untuk 18.6 m²)
- ✅ **Detail Tinggi** - Menghitung volume mortar per bata secara spesifik
- ⚠️ **Kompleks** - Memerlukan pemahaman teknis lebih dalam

### Metode Perhitungan
1. Hitung jumlah bata per m² berdasarkan:
   - Dimensi bata (panjang, lebar, tinggi)
   - Tebal adukan
   - Jenis pemasangan (setengah bata, satu bata, dll)

2. Hitung volume mortar per bata:
   ```
   Volume Top = (panjang × lebar × tebal) / 1,000,000
   Volume Right = (tinggi × lebar × tebal) / 1,000,000
   Total Volume = Volume Top + Volume Right
   ```

3. Hitung material berdasarkan formula mortar:
   ```
   Semen (kg) = Volume Mortar × Semen per m³ (dari interpolasi)
   Pasir (m³) = Volume Mortar × Pasir per m³ (dari interpolasi)
   Air (liter) = Volume Mortar × Air per m³ (dari interpolasi)
   ```

### Interpolasi Formula
Sistem menggunakan piecewise linear interpolation berdasarkan data empiris:
- Ratio 1:2 → 1018 kg semen/m³, 0.514 m³ pasir/m³
- Ratio 1:3 → 784 kg semen/m³, 0.626 m³ pasir/m³
- Ratio 1:4 → 322 kg semen/m³, 0.869 m³ pasir/m³
- Ratio 1:5 → 439 kg semen/m³, 0.797 m³ pasir/m³
- Ratio 1:6 → 384 kg semen/m³, 0.820 m³ pasir/m³
- Ratio 1:7 → 343 kg semen/m³, 0.838 m³ pasir/m³
- Ratio 1:8 → 311 kg semen/m³, 0.851 m³ pasir/m³

### Kapan Menggunakan Mode 1?
- ✅ Proyek konstruksi profesional
- ✅ Budget ketat, perlu efisiensi maksimal
- ✅ Sudah ada data empiris yang terverifikasi
- ✅ Memerlukan akurasi tinggi

### Contoh Hasil (18.6 m², Ratio 1:4)
- Semen: **110.95 kg** (2.22 sak)
- Pasir: **0.299 m³** (479 kg)
- Air: **119.82 liter**

---

## Mode 2: FIELD (Package Engineering)

### Deskripsi
Mode ini menggunakan rumus dari **rumus 2.xlsx** yang berbasis kemasan dengan engineering factors seperti shrinkage dan water percentage.

### Karakteristik
- ⚖️ **Balanced** - Tengah-tengah antara akurat dan praktis
- 📦 **Package-Based** - Berbasis sak semen/pasir
- 🔧 **Engineering Factors** - Mempertimbangkan shrinkage 15% dan water 30%
- 💼 **Field Proven** - Sudah digunakan di lapangan

### Metode Perhitungan
1. Konstanta Engineering:
   ```
   Volume Sak = 0.036 m³
   Shrinkage = 15%
   Water Percentage = 30%
   Water Factor = 0.2
   ```

2. Formula dari Excel (rumus 2.xlsx):
   ```
   Total Sak Ratio = Cement + Sand + (Water Factor × Water %)
   Volume per Luas = Total Sak Ratio × Vol Sak × (1 - Shrinkage)
   ```

3. Konversi ke material:
   ```
   Cement Sak = Total Sak × (Cement Ratio / Total Ratio)
   Sand Sak = Total Sak × (Sand Ratio / Total Ratio)
   Water = Total Sak × Vol Sak × Water % × 1000
   ```

### Engineering Factors
- **Shrinkage 15%**: Kompensasi susut volume saat pencampuran
- **Water 30%**: Persentase air dari total volume
- **Sak Volume 0.036 m³**: Volume efektif per sak (verified dari Excel)

### Kapan Menggunakan Mode 2?
- ✅ Proyek lapangan yang memerlukan kepraktisan
- ✅ Sudah terbiasa dengan sistem sak/kemasan
- ✅ Ingin faktor engineering sudah ter-built-in
- ✅ Reference dari rumus 2.xlsx

### Contoh Hasil (18.6 m², Ratio 1:4)
- Semen: **162.89 kg** (3.26 sak) - **+47% dari Mode 1**
- Pasir: **0.586 m³** (938 kg) - **+96% dari Mode 1**
- Air: **222.54 liter** - **+86% dari Mode 1**

---

## Mode 3: SIMPLE (Package Basic)

### Deskripsi
Mode ini adalah **rumus user awal yang sudah dikoreksi**, menggunakan pendekatan sederhana berbasis kemasan dengan asumsi realistis.

### Karakteristik
- 📊 **Paling Generous** - Material paling banyak (safety margin tinggi)
- 🎯 **Simpel** - Mudah dipahami dan dihitung
- 🔢 **Assumption-Based** - Berbasis asumsi 0.35 sak/m²
- ⚠️ **Over-Estimate** - Cenderung berlebih untuk safety

### Metode Perhitungan
1. Asumsi dasar:
   ```
   Semen per m² = 0.35 sak (estimasi realistis)
   Volume Sak = 0.03472 m³ (50kg/1440 kg/m³)
   Water = 30%
   ```

2. Perhitungan:
   ```
   Total Cement Sak = Luas × 0.35
   Total Sand Sak = Cement Sak × Sand Ratio
   Sand Volume = Sand Sak × Vol Sak
   Water = (Cement + Sand Sak) × Vol Sak × 30% × 1000
   ```

### Koreksi dari Rumus Awal
- **SEBELUM**: Volume sak = 0.012 m³ (tidak akurat)
- **SESUDAH**: Volume sak = 0.03472 m³ (50kg ÷ 1440 kg/m³)
- **SEBELUM**: Asumsi 1 sak = 1 m² (sangat tidak realistis)
- **SESUDAH**: Asumsi 0.35 sak = 1 m² (lebih realistis)

### Kapan Menggunakan Mode 3?
- ✅ Proyek kecil/rumahan
- ✅ Prefer safety margin tinggi
- ✅ Ingin perhitungan sederhana
- ✅ Tidak ada data empiris

### Contoh Hasil (18.6 m², Ratio 1:4)
- Semen: **325.50 kg** (6.51 sak) - **+193% dari Mode 1**
- Pasir: **0.904 m³** (1,447 kg) - **+202% dari Mode 1**
- Air: **339.04 liter** - **+183% dari Mode 1**

---

## Perbandingan Ketiga Mode

### Comparison Table (18.6 m², Ratio 1:4)

| Material | Mode 1 | Mode 2 | Mode 3 | Selisih Max-Min |
|----------|--------|--------|--------|-----------------|
| **Semen (kg)** | 110.95 | 162.89 | 325.50 | +193.4% |
| **Semen (sak 50kg)** | 2.22 | 3.26 | 6.51 | +193.4% |
| **Pasir (m³)** | 0.299 | 0.586 | 0.904 | +202.0% |
| **Air (liter)** | 119.82 | 222.54 | 339.04 | +183.0% |

### Analisis Perbedaan

#### Semen
- **Mode 1**: 110.95 kg (paling hemat)
- **Mode 2**: 162.89 kg (+47%)
- **Mode 3**: 325.50 kg (+193%) - **Hampir 3x lipat dari Mode 1**

#### Pasir
- **Mode 1**: 0.299 m³
- **Mode 2**: 0.586 m³ (+96%)
- **Mode 3**: 0.904 m³ (+202%) - **Lebih dari 3x lipat**

#### Air
- **Mode 1**: 119.82 liter
- **Mode 2**: 222.54 liter (+86%)
- **Mode 3**: 339.04 liter (+183%)

---

## Rekomendasi Penggunaan

### Mode 1: PROFESSIONAL ⭐⭐⭐⭐⭐
**Sangat Direkomendasikan untuk:**
- Proyek besar/menengah
- Budget terbatas
- Akurasi penting
- Punya data empiris

**Rating:**
- Akurasi: ⭐⭐⭐⭐⭐
- Efisiensi: ⭐⭐⭐⭐⭐
- Kemudahan: ⭐⭐⭐
- Safety: ⭐⭐⭐

### Mode 2: FIELD ⭐⭐⭐⭐
**Direkomendasikan untuk:**
- Proyek lapangan praktis
- Sudah terbiasa sistem sak
- Ingin engineering factors built-in
- Reference dari rumus 2.xlsx

**Rating:**
- Akurasi: ⭐⭐⭐⭐
- Efisiensi: ⭐⭐⭐
- Kemudahan: ⭐⭐⭐⭐
- Safety: ⭐⭐⭐⭐

### Mode 3: SIMPLE ⭐⭐⭐
**Alternatif untuk:**
- Proyek kecil/rumahan
- Prefer banyak safety margin
- Perhitungan sangat sederhana
- Tidak ada data empiris

**Rating:**
- Akurasi: ⭐⭐
- Efisiensi: ⭐⭐
- Kemudahan: ⭐⭐⭐⭐⭐
- Safety: ⭐⭐⭐⭐⭐

---

## Cara Penggunaan

### 1. Via Web Interface
Akses: `/brick-calculator/comparison`

**Langkah:**
1. Masukkan parameter:
   - Panjang dinding (meter)
   - Tinggi dinding (meter)
   - Jenis pemasangan (setengah bata, satu bata, dll)
   - Tebal adukan (cm)
   - Formula mortar atau custom ratio
   - Pilih bata (opsional)

2. Klik "Hitung Perbandingan"

3. Lihat hasil ketiga mode side-by-side

### 2. Via API
**Endpoint:** `POST /api/brick-calculator/compare-modes`

**Request:**
```json
{
  "wall_length": 6.2,
  "wall_height": 3.0,
  "installation_type_id": 1,
  "mortar_thickness": 1.0,
  "mortar_formula_id": 1,
  "brick_id": 1,
  "custom_cement_ratio": 1,
  "custom_sand_ratio": 4
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "mode_1_professional": { ... },
    "mode_2_field": { ... },
    "mode_3_simple": { ... },
    "input_params": { ... }
  },
  "explanation": { ... }
}
```

### 3. Via PHP Script
```php
use App\Services\BrickCalculationModes;

$params = [
    'wall_length' => 6.2,
    'wall_height' => 3.0,
    'installation_type_id' => 1,
    'mortar_thickness' => 1.0,
    'mortar_formula_id' => 1,
    'brick_id' => 1,
    'custom_cement_ratio' => 1,
    'custom_sand_ratio' => 4,
];

// Hitung semua mode sekaligus
$results = BrickCalculationModes::calculateAllModes($params);

// Atau hitung per mode
$mode1 = BrickCalculationModes::calculateProfessionalMode($params);
$mode2 = BrickCalculationModes::calculateFieldMode($params);
$mode3 = BrickCalculationModes::calculateSimpleMode($params);
```

---

## Technical Details

### File Structure
```
app/Services/BrickCalculationModes.php    # Core service class
app/Http/Controllers/
  BrickCalculationController.php          # Controller dengan 2 method baru:
                                          #   - compareThreeModes()
                                          #   - comparisonView()
routes/web.php                            # Route definitions
resources/views/brick_calculations/
  comparison.blade.php                    # UI untuk comparison
test_triple_mode_calculator.php           # Test script
```

### Dependencies
- Laravel Framework
- Eloquent ORM
- BrickInstallationType Model
- MortarFormula Model
- Brick Model

---

## Testing

### Run Test Script
```bash
php test_triple_mode_calculator.php
```

### Expected Output
```
═══════════════════════════════════════════════════════════════
TEST TRIPLE MODE CALCULATOR
═══════════════════════════════════════════════════════════════

INPUT PARAMETERS:
─────────────────────────────────────────────────────────────
Dinding: 6.2m × 3m = 18.6 m²
Tebal Adukan: 1 cm
Custom Ratio: 1:4

[... detailed output for each mode ...]

COMPARISON TABLE
[... comparison table ...]

ANALYSIS
[... analysis ...]

✅ Test selesai!
```

---

## FAQ

### Q: Mode mana yang paling akurat?
**A:** Mode 1 (Professional) adalah yang paling akurat karena berbasis data empiris terverifikasi dengan interpolasi linear.

### Q: Kenapa Mode 3 hasilnya hampir 3x lipat?
**A:** Mode 3 menggunakan asumsi konservatif (0.35 sak/m²) untuk safety margin. Ini cocok untuk pemula atau proyek kecil yang prefer kelebihan material daripada kekurangan.

### Q: Apakah bisa menggunakan ketiga mode bersamaan?
**A:** Ya! Gunakan comparison view untuk melihat ketiga hasil sekaligus dan pilih yang paling sesuai dengan kebutuhan proyek Anda.

### Q: Mode 2 dari mana datangnya?
**A:** Mode 2 ekstrak langsung dari file `rumus 2.xlsx`, menggunakan formula engineering yang sudah diverifikasi di lapangan dengan faktor shrinkage dan water percentage.

### Q: Boleh mengubah konstanta di Mode 2 atau 3?
**A:** Bisa, tapi perlu pemahaman teknis. Konstanta sudah di-tune berdasarkan data empiris. Perubahan sebaiknya didokumentasikan dan diverifikasi.

---

## Changelog

### Version 1.0.0 (2025-11-28)
- ✅ Implementasi Mode 1: Professional (Volume Mortar)
- ✅ Implementasi Mode 2: Field (dari rumus 2.xlsx)
- ✅ Implementasi Mode 3: Simple (rumus user dengan koreksi)
- ✅ Controller endpoints untuk API dan view
- ✅ Comparison UI untuk web interface
- ✅ Test script untuk verifikasi
- ✅ Dokumentasi lengkap

---

## Support & Contact

Untuk pertanyaan, bug reports, atau feature requests, silakan hubungi tim development.

---

**Created:** 2025-11-28
**Last Updated:** 2025-11-28
**Version:** 1.0.0
