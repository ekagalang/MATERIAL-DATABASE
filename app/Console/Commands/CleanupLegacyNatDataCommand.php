<?php

namespace App\Console\Commands;

use App\Models\Cement;
use App\Models\Nat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CleanupLegacyNatDataCommand extends Command
{
    protected $signature = 'nat:cleanup-legacy
        {--dry-run : Preview deletion without writing changes}
        {--include-unmapped : Also delete legacy Nat rows that are not mapped to nats}
        {--chunk=500 : Chunk size for deletion}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete legacy Nat rows from cements table after migration to nats table';

    public function handle(): int
    {
        if (!Schema::hasTable('nats') || !Schema::hasColumn('nats', 'legacy_cement_id')) {
            $this->warn('Legacy mapping column `nats.legacy_cement_id` is not available.');
            $this->line('Nat has been finalized as standalone material. Legacy cleanup command is no longer needed.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $includeUnmapped = (bool) $this->option('include-unmapped');
        $chunk = max(1, (int) $this->option('chunk'));

        $legacyQuery = Cement::query()->where('type', 'Nat');
        $mappedLegacyQuery = Cement::query()
            ->where('type', 'Nat')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('nats')->whereColumn('nats.legacy_cement_id', 'cements.id');
            });

        $legacyTotal = (clone $legacyQuery)->count();
        if ($legacyTotal === 0) {
            $this->warn('No legacy Nat rows found in cements table.');

            return self::SUCCESS;
        }

        $mappedTotal = (clone $mappedLegacyQuery)->count();
        $unmappedTotal = max(0, $legacyTotal - $mappedTotal);

        $targetQuery = $includeUnmapped ? $legacyQuery : $mappedLegacyQuery;
        $targetCount = (clone $targetQuery)->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Legacy Nat rows in cements', $legacyTotal],
                ['Mapped to nats (via legacy_cement_id)', $mappedTotal],
                ['Unmapped in cements', $unmappedTotal],
                ['Target rows to delete', $targetCount],
            ],
        );

        $this->line('Mode: ' . ($dryRun ? 'DRY-RUN (no writes)' : 'WRITE'));
        $this->line('Delete mode: ' . ($includeUnmapped ? 'ALL legacy Nat rows' : 'MAPPED ONLY (safe default)'));
        $this->line('Chunk size: ' . $chunk);

        if (!$includeUnmapped && $unmappedTotal > 0) {
            $this->warn(
                'There are unmapped legacy Nat rows. They will be kept. ' .
                    'Use --include-unmapped only if you are sure they are no longer needed.',
            );
        }

        if ($targetCount === 0) {
            $this->info('Nothing to delete.');

            return self::SUCCESS;
        }

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Proceed with deleting the targeted legacy Nat rows from cements?', false)) {
                $this->warn('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info('Dry-run completed. No data was deleted.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $progress = $this->output->createProgressBar($targetCount);
        $progress->start();

        $targetQuery->orderBy('id')->chunkById($chunk, function ($cements) use (&$deleted, $progress) {
            $ids = $cements->pluck('id')->all();
            if (empty($ids)) {
                return;
            }

            $deleted += Cement::query()->whereIn('id', $ids)->delete();
            $progress->advance(count($ids));
        });

        $progress->finish();
        $this->newLine(2);

        $legacyAfter = Cement::query()->where('type', 'Nat')->count();
        $mappedAfter = Nat::query()->whereNotNull('legacy_cement_id')->count();

        $this->table(
            ['Post-check', 'Count'],
            [
                ['Deleted rows', $deleted],
                ['Remaining legacy Nat rows in cements', $legacyAfter],
                ['Remaining mapped rows in nats (legacy_cement_id not null)', $mappedAfter],
            ],
        );

        $this->warn('Note: deleting cements rows sets nats.legacy_cement_id to NULL because of FK nullOnDelete().');
        $this->info('Legacy Nat cleanup completed.');

        return self::SUCCESS;
    }
}
