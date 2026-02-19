<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NatUnifyProductionCommand extends Command
{
    protected $signature = 'nat:unify-production
        {--dry-run : Preview execution steps without writing changes}
        {--drop-legacy : Run migration that drops legacy nats table}
        {--skip-maintenance : Do not toggle maintenance mode}
        {--skip-queue-restart : Do not run queue:restart}
        {--keep-down-on-failure : Keep app in maintenance mode when command fails}
        {--force : Skip confirmation prompt}';

    protected $description = 'Automate production rollout for NAT -> CEMENT unification safely';

    private const MIGRATION_ADD_KIND = 'database/migrations/2026_02_19_120000_add_material_kind_to_cements_table.php';
    private const MIGRATION_MERGE_NATS = 'database/migrations/2026_02_19_130000_merge_nats_into_cements_table.php';
    private const MIGRATION_DROP_LEGACY = 'database/migrations/2026_02_19_140000_drop_legacy_nats_table.php';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $dropLegacy = (bool) $this->option('drop-legacy');
        $skipMaintenance = (bool) $this->option('skip-maintenance');
        $skipQueueRestart = (bool) $this->option('skip-queue-restart');
        $keepDownOnFailure = (bool) $this->option('keep-down-on-failure');

        if (!Schema::hasTable('cements')) {
            $this->error('Table cements tidak ditemukan. Rollout dibatalkan.');
            return self::FAILURE;
        }

        $this->line('=== NAT UNIFICATION PRODUCTION ROLLOUT ===');
        $this->line('Mode: ' . ($dryRun ? 'DRY-RUN (no writes)' : 'WRITE'));
        $this->line('Drop legacy nats table: ' . ($dropLegacy ? 'yes' : 'no'));
        $this->line('Maintenance mode: ' . ($skipMaintenance ? 'skip' : 'enabled'));
        $this->line('Queue restart: ' . ($skipQueueRestart ? 'skip' : 'enabled'));
        $this->newLine();

        $pre = $this->snapshotCounts();
        $this->renderSnapshot('Pre-check', $pre);

        if ($dryRun) {
            $this->line('Planned steps:');
            $this->line('1) migrate --path=' . self::MIGRATION_ADD_KIND);
            $this->line('2) migrate --path=' . self::MIGRATION_MERGE_NATS);
            $this->line('3) verify references and counts');
            if ($dropLegacy) {
                $this->line('4) migrate --path=' . self::MIGRATION_DROP_LEGACY);
            }
            $this->line('5) queue:restart');
            $this->line('6) app up');
            $this->info('Dry-run selesai.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $continue = $this->confirm(
                'Pastikan backup database SUDAH dibuat. Lanjutkan rollout NAT -> CEMENT sekarang?',
                false,
            );

            if (!$continue) {
                $this->warn('Rollout dibatalkan oleh user.');
                return self::SUCCESS;
            }
        }

        $initiallyDown = app()->isDownForMaintenance();
        $enteredMaintenance = false;

        try {
            if (!$skipMaintenance && !$initiallyDown) {
                $this->callArtisan('down', ['--retry' => 60], 'Mengaktifkan maintenance mode');
                $enteredMaintenance = true;
            }

            $this->runMigrationByPath(self::MIGRATION_ADD_KIND, 'Menjalankan migrasi add material_kind');
            $this->runMigrationByPath(self::MIGRATION_MERGE_NATS, 'Menjalankan migrasi merge nats -> cements');

            $issues = $this->validatePostMerge();
            if (!empty($issues)) {
                $this->error('Validasi pasca-merge menemukan masalah:');
                foreach ($issues as $issue) {
                    $this->line('- ' . $issue);
                }
                return self::FAILURE;
            }

            if ($dropLegacy) {
                $this->runMigrationByPath(self::MIGRATION_DROP_LEGACY, 'Menjalankan migrasi drop legacy nats');
            }

            if (!$skipQueueRestart) {
                $this->callArtisan('queue:restart', [], 'Restart queue worker');
            }

            $post = $this->snapshotCounts();
            $this->renderSnapshot('Post-check', $post);

            if (!$skipMaintenance && !$initiallyDown) {
                $this->callArtisan('up', [], 'Menonaktifkan maintenance mode');
            }

            $this->info('Rollout NAT -> CEMENT selesai dengan sukses.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Rollout gagal: ' . $e->getMessage());

            if (!$skipMaintenance && !$initiallyDown && $enteredMaintenance && !$keepDownOnFailure) {
                try {
                    $this->callArtisan('up', [], 'Recovery: menonaktifkan maintenance mode');
                } catch (\Throwable $inner) {
                    $this->error('Gagal recovery app up: ' . $inner->getMessage());
                }
            }

            if ($keepDownOnFailure && $enteredMaintenance) {
                $this->warn('App tetap maintenance mode (--keep-down-on-failure).');
            }

            return self::FAILURE;
        }
    }

    /**
     * @return array{nats_table_exists: bool, legacy_nats_rows: int, nat_rows_in_cements: int, cements_total: int}
     */
    private function snapshotCounts(): array
    {
        $natsTableExists = Schema::hasTable('nats');

        return [
            'nats_table_exists' => $natsTableExists,
            'legacy_nats_rows' => $natsTableExists ? (int) DB::table('nats')->count() : 0,
            'nat_rows_in_cements' => Schema::hasColumn('cements', 'material_kind')
                ? (int) DB::table('cements')->where('material_kind', 'nat')->count()
                : 0,
            'cements_total' => (int) DB::table('cements')->count(),
        ];
    }

    /**
     * @param  array{nats_table_exists: bool, legacy_nats_rows: int, nat_rows_in_cements: int, cements_total: int}  $snapshot
     */
    private function renderSnapshot(string $title, array $snapshot): void
    {
        $this->line($title . ':');
        $this->table(
            ['Metric', 'Value'],
            [
                ['nats table exists', $snapshot['nats_table_exists'] ? 'yes' : 'no'],
                ['rows in legacy nats', $snapshot['legacy_nats_rows']],
                ['rows in cements (material_kind=nat)', $snapshot['nat_rows_in_cements']],
                ['total rows in cements', $snapshot['cements_total']],
            ],
        );
    }

    private function runMigrationByPath(string $path, string $label): void
    {
        if (!file_exists(base_path($path))) {
            throw new \RuntimeException('File migration tidak ditemukan: ' . $path);
        }

        $this->callArtisan(
            'migrate',
            ['--path' => $path, '--force' => true],
            $label,
        );
    }

    /**
     * @return array<int, string>
     */
    private function validatePostMerge(): array
    {
        $issues = [];

        if (!Schema::hasColumn('cements', 'material_kind')) {
            $issues[] = 'Kolom cements.material_kind tidak ditemukan setelah merge.';
            return $issues;
        }

        if (Schema::hasTable('nats')) {
            $legacyCount = (int) DB::table('nats')->count();
            $natCount = (int) DB::table('cements')->where('material_kind', 'nat')->count();
            if ($legacyCount > 0 && $natCount === 0) {
                $issues[] = 'Legacy nats masih ada, tetapi tidak ditemukan row nat di cements.';
            }
        }

        if (Schema::hasTable('brick_calculations') && Schema::hasColumn('brick_calculations', 'nat_id')) {
            $brokenBrickCalc = DB::table('brick_calculations as bc')
                ->leftJoin('cements as c', function ($join) {
                    $join->on('c.id', '=', 'bc.nat_id')->where('c.material_kind', '=', 'nat');
                })
                ->whereNotNull('bc.nat_id')
                ->whereNull('c.id')
                ->count();

            if ($brokenBrickCalc > 0) {
                $issues[] = "Ada {$brokenBrickCalc} row brick_calculations.nat_id yang tidak valid.";
            }
        }

        if (Schema::hasTable('recommended_combinations') && Schema::hasColumn('recommended_combinations', 'nat_id')) {
            $brokenRecommended = DB::table('recommended_combinations as rc')
                ->leftJoin('cements as c', function ($join) {
                    $join->on('c.id', '=', 'rc.nat_id')->where('c.material_kind', '=', 'nat');
                })
                ->whereNotNull('rc.nat_id')
                ->whereNull('c.id')
                ->count();

            if ($brokenRecommended > 0) {
                $issues[] = "Ada {$brokenRecommended} row recommended_combinations.nat_id yang tidak valid.";
            }
        }

        if (
            Schema::hasTable('store_material_availabilities') &&
            Schema::hasColumn('store_material_availabilities', 'materialable_type') &&
            Schema::hasColumn('store_material_availabilities', 'materialable_id')
        ) {
            $brokenAvailabilities = DB::table('store_material_availabilities as sma')
                ->leftJoin('cements as c', function ($join) {
                    $join->on('c.id', '=', 'sma.materialable_id')->where('c.material_kind', '=', 'nat');
                })
                ->where('sma.materialable_type', 'App\\Models\\Nat')
                ->whereNull('c.id')
                ->count();

            if ($brokenAvailabilities > 0) {
                $issues[] = "Ada {$brokenAvailabilities} row store_material_availabilities App\\\\Models\\\\Nat yang tidak valid.";
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callArtisan(string $command, array $arguments, string $label): void
    {
        $this->line($label . '...');
        $exitCode = Artisan::call($command, $arguments);
        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        if ($exitCode !== 0) {
            throw new \RuntimeException("Command {$command} gagal dengan exit code {$exitCode}.");
        }
    }
}

