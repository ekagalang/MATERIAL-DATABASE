<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunningCommand extends Command
{
    protected $signature = 'gass';

    protected $description = 'Run Laravel server and Vite (npm run dev) together';

    public function handle()
    {
        $host = '127.0.0.1';
        $port = '8000';
        $url = "http://{$host}:{$port}";

        $this->info('🔥 Starting Laravel server...');
        $this->info('🔥 Starting npm dev...');
        $this->newLine();

        $this->info("🌐 App URL : {$url}");
        $this->info("❌ Press 'Q' then ENTER to stop server");
        $this->newLine();

        // Artisan serve
        $artisan = new Process([
            'php',
            'artisan',
            'serve',
            "--host={$host}",
            "--port={$port}",
        ]);

        // npm dev
        $npmCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'npm.cmd'
            : 'npm';

        $npm = new Process([$npmCmd, 'run', 'dev']);


        $artisan->setTimeout(null);
        $npm->setTimeout(null);

        // Start processes
        $artisan->start();
        $npm->start();

        // Non blocking input
        stream_set_blocking(STDIN, false);

        while (true) {

            // Laravel output
            $artisanOut = $artisan->getIncrementalOutput();
            $artisanErr = $artisan->getIncrementalErrorOutput();

            if ($artisanOut) {
                echo "[LARAVEL] " . $artisanOut;
            }

            if ($artisanErr) {
                echo "[LARAVEL-ERR] " . $artisanErr;
            }

            // NPM output
            $npmOut = $npm->getIncrementalOutput();
            $npmErr = $npm->getIncrementalErrorOutput();

            if ($npmOut) {
                echo "[VITE] " . $npmOut;
            }

            if ($npmErr) {
                echo "[VITE-ERR] " . $npmErr;
            }

            // Stop if process died
            if (!$artisan->isRunning() || !$npm->isRunning()) {
                $this->error("\n❌ One process stopped unexpectedly.");
                break;
            }

            // Read input
            $input = fgets(STDIN);

            if ($input !== false && strtolower(trim($input)) === 'q') {

                $this->warn("\n🛑 Stopping servers...");

                $artisan->stop(1);
                $npm->stop(1);

                $this->info('✅ Server stopped.');
                break;
            }

            usleep(100000); // 0.1s
        }

        return self::SUCCESS;
    }
}
