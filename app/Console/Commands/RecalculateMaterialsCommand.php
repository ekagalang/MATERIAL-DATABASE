<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;
use App\Models\Cat;
use App\Models\Ceramic;
use Illuminate\Database\Eloquent\Collection;

class RecalculateMaterialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materials:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate volume and comparison prices for all materials to ensure consistency with NumberHelper rules.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting material recalculation...');

        $this->recalculateBricks();
        $this->recalculateCements();
        $this->recalculateSands();
        $this->recalculateCats();
        $this->recalculateCeramics();

        $this->info('All materials have been successfully recalculated.');
        return 0;
    }

    protected function recalculateBricks()
    {
        $this->line('Processing Bricks...');
        $bricks = Brick::all();
        $progressBar = $this->output->createProgressBar($bricks->count());

        $bricks->each(function ($brick) use ($progressBar) {
            $brick->calculateVolume();
            $brick->calculateComparisonPrice();
            $brick->saveQuietly(); // Use saveQuietly to avoid triggering observers if not needed
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function recalculateCements()
    {
        $this->line('Processing Cements...');
        $cements = Cement::all();
        $progressBar = $this->output->createProgressBar($cements->count());

        $cements->each(function ($cement) use ($progressBar) {
            $cement->calculateNetWeight();
            $cement->calculateComparisonPrice();
            $cement->saveQuietly();
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function recalculateSands()
    {
        $this->line('Processing Sands...');
        $sands = Sand::all();
        $progressBar = $this->output->createProgressBar($sands->count());

        $sands->each(function ($sand) use ($progressBar) {
            $sand->calculateVolume();
            $sand->calculateComparisonPrice();
            $sand->saveQuietly();
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function recalculateCats()
    {
        $this->line('Processing Cats...');
        $cats = Cat::all();
        $progressBar = $this->output->createProgressBar($cats->count());

        $cats->each(function ($cat) use ($progressBar) {
            $cat->calculateNetWeight();
            $cat->calculateComparisonPrice();
            $cat->saveQuietly();
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function recalculateCeramics()
    {
        $this->line('Processing Ceramics...');
        $ceramics = Ceramic::all();
        $progressBar = $this->output->createProgressBar($ceramics->count());

        $ceramics->each(function ($ceramic) use ($progressBar) {
            $ceramic->calculateCoverage();
            $ceramic->calculateComparisonPrice();
            $ceramic->saveQuietly();
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);
    }
}
