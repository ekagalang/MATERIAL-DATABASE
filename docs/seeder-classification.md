# Seeder Classification

Dokumen ini menjelaskan seeder mana yang wajib dijalankan lewat `DatabaseSeeder`, mana yang opsional, dan mana yang hanya berfungsi sebagai pendukung.

## Wajib di `DatabaseSeeder`

Seeder berikut dibutuhkan agar aplikasi punya data master/reference yang dipakai saat runtime:

- `AccessControlSeeder`
  Membuat permission, role, dan admin default.

- `UnitSeeder`
  Membuat satuan material dan relasinya ke jenis material.

- `MaterialSettingSeeder`
  Mengatur visibilitas dan urutan jenis material di aplikasi.

- `BrickInstallationTypeSeeder`
  Membuat master tipe pemasangan bata seperti `half`, `one`, `quarter`, dan `rollag`.
  Data ini masih dipakai controller, validasi, model, dan formula bata.

- `MortarFormulaSeeder`
  Membuat master formula adukan yang dipakai kalkulasi bata dan pekerjaan terkait.

- `WorkTaxonomySeeder`
  Membuat taxonomy pekerjaan seperti lantai, area, field, dan grouping default berdasarkan formula yang tersedia.

## Opsional, dijalankan manual saat dibutuhkan

Seeder berikut tidak wajib untuk production bootstrap. Jalankan hanya jika environment membutuhkan data dummy atau sample.

- `MassDataSeeder`
  Mengisi data dummy material massal lintas toko untuk `brick`, `cement`, `sand`, `cat`, `ceramic`, dan `nat`.
  Cocok untuk demo, development, atau testing dengan data material dummy dalam jumlah besar.

- `BrickCalculationDataSeeder`
  Membuat sample hasil kalkulasi.
  Murni untuk demo/testing dan tidak dibutuhkan untuk aplikasi berjalan normal.

## Catatan penting

- `MassDataSeeder` sekarang juga mengisi dummy `nat` ke tabel `cements` dengan `material_kind = 'nat'`.
- Seeder dummy material sebaiknya tidak dijalankan default di production agar database awal tetap bersih dan fokus pada master data.
