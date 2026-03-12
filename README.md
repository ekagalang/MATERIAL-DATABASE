# DevMaterial

Aplikasi Laravel untuk manajemen material bangunan, rekomendasi kombinasi material, dan kalkulasi pekerjaan seperti bata, cat, screed, acian, dan keramik.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Node.js 20+ dan npm
- Vite
- Queue driver: `database`
- Session driver: `database`
- Cache store: `database`

## Struktur Seeder

Seeder dibagi menjadi dua kelompok:

- Seeder wajib untuk bootstrap sistem
- Seeder opsional untuk dummy/sample data

Rincian lengkapnya ada di [docs/seeder-classification.md](docs/seeder-classification.md).

## Setup Dari Nol

### 1. Clone repository

```bash
git clone <repository-url>
cd DevMaterial
```

### 2. Install dependency backend dan frontend

```bash
composer install
npm install
```

### 3. Siapkan file environment

```bash
copy .env.example .env
```

Kalau di Linux/macOS:

```bash
cp .env.example .env
```

### 4. Isi konfigurasi `.env`

Minimal sesuaikan nilai berikut:

```env
APP_NAME=DevMaterial
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devmaterial
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

TELESCOPE_ENABLED=false
```

Kalau memakai Google Maps API untuk fitur lokasi:

```env
GOOGLE_MAPS_API_KEY=your-key
```

### 5. Generate app key

```bash
php artisan key:generate
```

### 6. Buat database

Buat database MySQL kosong, misalnya `devmaterial`, lalu jalankan migrasi:

```bash
php artisan migrate
```

### 7. Seed data master wajib

```bash
php artisan db:seed
```

Seeder default ini hanya mengisi data yang dibutuhkan aplikasi untuk berjalan normal:

- role, permission, dan admin default
- satuan material
- material setting
- tipe pemasangan bata
- formula adukan
- taxonomy pekerjaan

Seeder dummy material tidak dijalankan otomatis.

### 8. Buat symbolic link storage

```bash
php artisan storage:link
```

### 9. Build asset frontend

Untuk development:

```bash
npm run dev
```

Untuk production:

```bash
npm run build
```

### 10. Jalankan aplikasi lokal

Cara paling cepat:

```bash
composer run dev
```

Script ini akan menjalankan:

- Laravel dev server
- queue listener
- Vite dev server

Kalau ingin manual:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

## Setup Local Lengkap

Urutan yang direkomendasikan untuk environment development baru:

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
composer run dev
```

Kalau ingin reset total database local:

```bash
php artisan migrate:fresh
php artisan db:seed
```

## Menambahkan Dummy Material

Dummy material tidak dimasukkan ke `DatabaseSeeder` agar production bootstrap tetap bersih.

Kalau ragu memilih command:

- untuk local/dev harian, gunakan `php artisan materials:reset-store-dataset --force`
- untuk data dummy material massal berbasis store/address lama, gunakan `php artisan db:seed --class=MassDataSeeder`
- untuk production fresh install, jangan jalankan seeder dummy kecuali memang dibutuhkan

### Rekomendasi untuk local/dev

Kalau tujuanmu adalah mencoba fitur kalkulasi yang memakai store, store location, dan availability, jalur yang paling aman adalah:

```bash
php artisan materials:reset-store-dataset --force
```

Command ini lebih lengkap daripada seeder dummy biasa karena akan:

- mengosongkan dataset material/store lama
- membuat store dan store location
- membuat material dummy
- menghubungkan material ke `store_location_id`
- mengisi relasi `store_material_availabilities`

Ini adalah dataset dummy yang paling cocok untuk development harian dan pengujian fitur store-based calculation.

### Dummy material massal

```bash
php artisan db:seed --class=MassDataSeeder
```

Seeder ini mengisi dummy:

- brick
- cement
- sand
- cat
- ceramic
- nat

Catatan penting:

- `MassDataSeeder` adalah seeder dummy model lama
- seeder ini mengisi kolom `store` dan `address`, tetapi tidak dirancang sebagai bootstrap penuh untuk sistem store/location baru
- jika setelah menjalankan `MassDataSeeder` kamu ingin memakai fitur store-based calculation, jalankan migrasi store berikut:

```bash
php artisan stores:migrate
```

Kalau perlu repair linking existing material ke availability pivot:

```bash
php artisan stores:backfill-availability
```

### Sample calculation

Kalau butuh data contoh hasil kalkulasi:

```bash
php artisan db:seed --class=BrickCalculationDataSeeder
```

## Admin Default

`AccessControlSeeder` akan membuat user admin default berdasarkan env berikut:

```env
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@hope2.kanggo
ADMIN_PASSWORD=password
```

Sangat disarankan mengganti nilai ini di environment non-local.

## Testing

Menjalankan seluruh test:

```bash
php artisan test
```

Atau:

```bash
composer test
```

Menjalankan test tertentu:

```bash
php artisan test --filter Materials
```

## Formatting

Format frontend, Blade, PHP, CSS, dan JSON:

```bash
npm run format
```

Kalau memakai Laravel Pint:

```bash
vendor/bin/pint
```

## Menjalankan di Production

Berikut setup minimum yang direkomendasikan.

### 1. Siapkan server

Minimal:

- Linux server dengan Nginx atau Apache
- PHP 8.2+ dengan ekstensi yang dibutuhkan Laravel
- MySQL
- Node.js hanya dibutuhkan saat build asset

Direkomendasikan:

- Nginx + PHP-FPM
- process manager untuk queue worker, misalnya Supervisor atau systemd

### 2. Deploy source code

```bash
git clone <repository-url> /var/www/devmaterial
cd /var/www/devmaterial
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
```

### 3. Isi `.env` production

Contoh minimum:

```env
APP_NAME=DevMaterial
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devmaterial
DB_USERNAME=devmaterial_user
DB_PASSWORD=strong-password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=warning

TELESCOPE_ENABLED=false
```

Opsional, sesuaikan kebutuhan kalkulasi:

```env
MATERIALS_TOPK_BUFFER_ENABLED=true
MATERIALS_COMBINATION_COMPLEXITY_MAX_ESTIMATED=5000
MATERIALS_COMPLEXITY_FAST_MODE_ENABLED=true
MATERIALS_COMPLEXITY_FAST_MODE_CAP_PER_MATERIAL=2
MATERIALS_PERFORMANCE_LOG_DEBUG=false
MATERIALS_TOPK_BUFFER_LOG_DEBUG=false
MATERIALS_COMBINATION_COMPLEXITY_LOG_DEBUG=false
```

### 4. Generate key, migrate, dan seed

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Kalau production memang membutuhkan dummy material, jalankan manual dan sadar risikonya:

```bash
php artisan db:seed --class=MassDataSeeder --force
```

Normalnya production tidak perlu seeder dummy ini.

### 5. Optimasi Laravel

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Jalankan queue worker

Karena aplikasi memakai `QUEUE_CONNECTION=database`, worker wajib hidup untuk job async.

Contoh manual:

```bash
php artisan queue:work --queue=default --tries=1
```

Atau jika ingin semua queue:

```bash
php artisan queue:work --tries=1
```

Di production sebaiknya jangan dijalankan manual di terminal. Gunakan Supervisor atau systemd.

Contoh perintah worker yang umum dipakai:

```bash
php artisan queue:work --sleep=3 --tries=1 --max-time=3600
```

### 7. Web server

Pastikan document root mengarah ke folder:

```text
public/
```

Jangan arahkan document root ke root repository.

### 8. Permission yang umum diperlukan

Pastikan web server user bisa menulis ke:

- `storage/`
- `bootstrap/cache/`

### 9. Checklist production

- `.env` sudah `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` sesuai domain final
- database sudah termigrasi
- `php artisan db:seed --force` sudah dijalankan
- asset `npm run build` sudah selesai
- queue worker aktif
- storage link tersedia
- permission folder benar

## Command Custom yang Relevan

Tidak semua command custom wajib dijalankan.

### Wajib untuk fresh install

Tidak ada command custom tambahan yang wajib untuk fresh install selain alur standar berikut:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Di production:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

### Wajib jika butuh dataset dummy store-based yang lengkap

```bash
php artisan materials:reset-store-dataset --force
```

Gunakan ini untuk local/dev kalau ingin langsung mendapat dataset store/material yang konsisten.

### Wajib jika memakai `MassDataSeeder` dan ingin masuk ke sistem store baru

```bash
php artisan stores:migrate
```

Opsional repair tambahan:

```bash
php artisan stores:backfill-availability
```

### Wajib hanya untuk rollout legacy NAT production

Kalau server production adalah upgrade dari struktur lama NAT, gunakan command rollout berikut:

```bash
php artisan nat:unify-production --force
```

Ini bukan command untuk fresh install. Ini hanya dipakai saat migrasi data legacy NAT ke struktur `cements.material_kind = nat`.

## Perbedaan `materials:reset-store-dataset` dan `stores:migrate`

Kedua command ini tidak setara dan dipakai untuk kebutuhan yang berbeda.

### `php artisan materials:reset-store-dataset --force`

Gunakan command ini jika ingin menyiapkan dataset dummy store-based yang bersih untuk local/dev.

Karakteristik:

- destructive
- menghapus data material, store, store location, availability, dan data kalkulasi terkait
- membuat ulang dataset dummy dari nol
- langsung membentuk store, store location, material, dan relasi availability yang konsisten

Cocok untuk:

- local development
- demo environment
- reset total dataset dummy

Tidak cocok untuk:

- production
- environment yang datanya ingin dipertahankan

### `php artisan stores:migrate`

Gunakan command ini jika kamu sudah punya data material lama dan ingin memindahkannya ke sistem store/location baru tanpa membuat dataset dummy baru.

Karakteristik:

- non-destructive
- membaca `store` dan `address` dari material yang sudah ada
- membuat `stores` dan `store_locations` jika belum ada
- mengisi `store_location_id`
- menghubungkan material existing ke relasi store yang baru

Cocok untuk:

- migrasi data existing
- hasil seeder lama
- data import lama yang belum punya `store_location_id`

Tidak cocok untuk:

- reset total data dummy
- kebutuhan membuat dataset dev dari nol secara cepat

### Ringkasnya

- `materials:reset-store-dataset --force`
  Hapus dan buat ulang dataset dummy store-based dari nol.

- `stores:migrate`
  Migrasikan data material yang sudah ada ke sistem store/location baru.

## Troubleshooting Singkat

### Class seeder tidak ditemukan

Jalankan:

```bash
composer dump-autoload
```

### Queue async tidak jalan

Pastikan worker aktif:

```bash
php artisan queue:work --tries=1
```

### Session atau cache error

Karena project ini memakai `database` untuk session dan cache, pastikan tabel hasil migrasi sudah ada dan koneksi DB sehat.

### Dummy material `nat` tidak muncul

Jalankan:

```bash
php artisan db:seed --class=MassDataSeeder
```

## Referensi Tambahan

- Seeder classification: [docs/seeder-classification.md](docs/seeder-classification.md)
