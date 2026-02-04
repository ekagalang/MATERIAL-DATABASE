#!/usr/bin/env bash
set -Eeuo pipefail

DRY_RUN=0
ASSUME_YES=0
SKIP_COMPOSER=0
SKIP_NPM=0
SKIP_DOWN=0
SKIP_STORES_LINK=0
MAINTENANCE_ENABLED=0

usage() {
    cat <<'EOF'
Usage: ./scripts/deploy-nat-production.sh [options]

Options:
  --dry-run            Print commands only (no execution)
  --yes                Skip interactive confirmation
  --skip-composer      Skip composer install
  --skip-npm           Skip npm ci + npm run build
  --skip-down          Do not put app into maintenance mode
  --skip-stores-link   Skip stores:migrate --link-only --force
  -h, --help           Show this help
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=1 ;;
        --yes) ASSUME_YES=1 ;;
        --skip-composer) SKIP_COMPOSER=1 ;;
        --skip-npm) SKIP_NPM=1 ;;
        --skip-down) SKIP_DOWN=1 ;;
        --skip-stores-link) SKIP_STORES_LINK=1 ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage
            exit 1
            ;;
    esac
    shift
done

run() {
    echo ">> $*"
    if [[ "$DRY_RUN" -eq 0 ]]; then
        "$@"
    fi
}

on_error() {
    local code=$?
    echo ""
    echo "Deploy gagal (exit code: $code)." >&2
    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        echo "Aplikasi masih maintenance mode. Setelah perbaikan jalankan: php artisan up" >&2
    fi
    exit "$code"
}

trap on_error ERR

if [[ ! -f "./artisan" ]]; then
    echo "File artisan tidak ditemukan. Jalankan script dari root project Laravel." >&2
    exit 1
fi

echo "=== NAT Production Deployment (Ubuntu) ==="
echo "Checklist manual sebelum lanjut:"
echo "1) Backup database production"
echo "2) Backup storage/app/public (foto/file)"
echo "3) Pastikan maintenance window siap"
echo ""

if [[ "$ASSUME_YES" -eq 0 ]]; then
    read -r -p "Ketik 'yes' untuk lanjut: " confirm
    if [[ "$confirm" != "yes" ]]; then
        echo "Dibatalkan."
        exit 0
    fi
fi

if [[ "$SKIP_DOWN" -eq 0 ]]; then
    run php artisan down
    MAINTENANCE_ENABLED=1
else
    echo ">> Skip maintenance mode (php artisan down)"
fi

if [[ "$SKIP_COMPOSER" -eq 0 ]]; then
    run composer install --no-dev --optimize-autoloader
else
    echo ">> Skip composer install"
fi

if [[ "$SKIP_NPM" -eq 0 ]]; then
    run npm ci
    run npm run build
else
    echo ">> Skip npm build"
fi

# Nat migration phases (safe to re-run)
run php artisan migrate --path=database/migrations/2026_02_04_000001_create_nats_table.php --force
run php artisan migrate --path=database/migrations/2026_02_04_000002_add_nat_v2_columns_to_calculations_and_recommendations.php --force
run php artisan migrate --path=database/migrations/2026_02_04_000003_finalize_nat_foreign_keys_to_nats.php --force

# Legacy migration/cleanup (commands no-op when legacy already finalized)
run php artisan nat:migrate-legacy --force
run php artisan nat:cleanup-legacy --force

if [[ "$SKIP_STORES_LINK" -eq 0 ]]; then
    run php artisan stores:migrate --link-only --force
else
    echo ">> Skip stores:migrate --link-only --force"
fi

# Final Nat schema cleanup
run php artisan migrate --path=database/migrations/2026_02_04_000004_drop_legacy_cement_id_from_nats_table.php --force
run php artisan migrate --path=database/migrations/2026_02_04_000005_add_type_to_nats_table.php --force

# Apply any pending migrations
run php artisan migrate --force

# Refresh caches/runtime
run php artisan optimize:clear
run php artisan config:cache
run php artisan route:cache
run php artisan view:cache
run php artisan queue:restart

if [[ "$SKIP_DOWN" -eq 0 ]]; then
    run php artisan up
    MAINTENANCE_ENABLED=0
fi

echo ""
echo "Deploy Nat production selesai."
echo "Quick check:"
echo "- Nat CRUD (type tersimpan)"
echo "- Material tab Nat & autosuggest"
echo "- Perhitungan/rekomendasi yang memakai nat"
