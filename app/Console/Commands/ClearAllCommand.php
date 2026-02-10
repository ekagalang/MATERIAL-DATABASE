<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sikat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all Laravel cache (config, route, view, app, optimize)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Starting Laravel cache cleanup...');
        $this->newLine();

        $commands = [
            'cache:clear' => '✔ Application cache cleared',
            'config:clear' => '✔ Configuration cache cleared',
            'route:clear' => '✔ Route cache cleared',
            'view:clear' => '✔ View cache cleared',
            'optimize:clear' => '✔ Optimization cache cleared',
        ];

        foreach ($commands as $command => $message) {
            $this->line("Running: php artisan {$command}");
            $this->call($command);
            $this->info($message);
            $this->newLine();
        }

        $this->info('✅ All Laravel cache successfully cleared!');
        $this->info('🚀 Project is fresh and ready to run.');

        return self::SUCCESS;
    }
}
