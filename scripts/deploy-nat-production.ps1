param(
    [switch]$SkipComposer,
    [switch]$SkipNpm,
    [switch]$SkipDown,
    [switch]$SkipStoresLink,
    [switch]$DryRun,
    [switch]$AssumeYes
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Run-Command {
    param(
        [Parameter(Mandatory = $true)][string]$Exe,
        [Parameter(Mandatory = $true)][string[]]$Args
    )

    $display = "$Exe $($Args -join ' ')"
    Write-Host ">> $display" -ForegroundColor Cyan

    if ($DryRun) {
        return
    }

    & $Exe @Args
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed: $display"
    }
}

if (-not (Test-Path -Path ".\artisan")) {
    throw "File artisan tidak ditemukan. Jalankan script ini dari root project Laravel."
}

Write-Host "=== NAT Production Deployment ===" -ForegroundColor Yellow
Write-Host "Checklist manual sebelum lanjut:"
Write-Host "1) Backup database production"
Write-Host "2) Backup storage/app/public (foto/file)"
Write-Host "3) Pastikan maintenance window siap"
Write-Host ""

if (-not $AssumeYes) {
    $confirm = Read-Host "Ketik 'yes' untuk lanjut"
    if ($confirm -ne 'yes') {
        Write-Host "Dibatalkan."
        exit 0
    }
}

$maintenanceEnabled = $false

try {
    if (-not $SkipDown) {
        Run-Command -Exe "php" -Args @("artisan", "down")
        $maintenanceEnabled = $true
    } else {
        Write-Host ">> Skip maintenance mode (php artisan down)"
    }

    if (-not $SkipComposer) {
        Run-Command -Exe "composer" -Args @("install", "--no-dev", "--optimize-autoloader")
    } else {
        Write-Host ">> Skip composer install"
    }

    if (-not $SkipNpm) {
        Run-Command -Exe "npm" -Args @("ci")
        Run-Command -Exe "npm" -Args @("run", "build")
    } else {
        Write-Host ">> Skip npm build"
    }

    # Tahap migrasi Nat (aman untuk diulang)
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--path=database/migrations/2026_02_04_000001_create_nats_table.php", "--force")
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--path=database/migrations/2026_02_04_000002_add_nat_v2_columns_to_calculations_and_recommendations.php", "--force")
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--path=database/migrations/2026_02_04_000003_finalize_nat_foreign_keys_to_nats.php", "--force")

    # Migrasi + cleanup data legacy (command akan no-op jika legacy sudah final)
    Run-Command -Exe "php" -Args @("artisan", "nat:migrate-legacy", "--force")
    Run-Command -Exe "php" -Args @("artisan", "nat:cleanup-legacy", "--force")

    if (-not $SkipStoresLink) {
        Run-Command -Exe "php" -Args @("artisan", "stores:migrate", "--link-only", "--force")
    } else {
        Write-Host ">> Skip stores:migrate --link-only --force"
    }

    # Finalisasi schema Nat
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--path=database/migrations/2026_02_04_000004_drop_legacy_cement_id_from_nats_table.php", "--force")
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--path=database/migrations/2026_02_04_000005_add_type_to_nats_table.php", "--force")

    # Sisa migration lain (jika ada pending)
    Run-Command -Exe "php" -Args @("artisan", "migrate", "--force")

    # Refresh cache/runtime
    Run-Command -Exe "php" -Args @("artisan", "optimize:clear")
    Run-Command -Exe "php" -Args @("artisan", "config:cache")
    Run-Command -Exe "php" -Args @("artisan", "route:cache")
    Run-Command -Exe "php" -Args @("artisan", "view:cache")
    Run-Command -Exe "php" -Args @("artisan", "queue:restart")

    if (-not $SkipDown) {
        Run-Command -Exe "php" -Args @("artisan", "up")
        $maintenanceEnabled = $false
    }

    Write-Host ""
    Write-Host "Deploy Nat production selesai." -ForegroundColor Green
    Write-Host "Quick check:"
    Write-Host "- Nat CRUD (type tersimpan)"
    Write-Host "- Material tab Nat & autosuggest"
    Write-Host "- Perhitungan/rekomendasi yang memakai nat"
}
catch {
    Write-Error $_
    if ($maintenanceEnabled) {
        Write-Warning "Aplikasi masih maintenance mode. Setelah fix, jalankan: php artisan up"
    }
    exit 1
}
