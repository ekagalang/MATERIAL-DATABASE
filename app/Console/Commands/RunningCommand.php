<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunningCommand extends Command
{
    protected $signature = 'gass';
    protected $description = 'Run Laravel server and Vite (npm run dev) together';

    public function handle()
    {
        $host = '127.0.0.1';
        $port = '8000';
        $url  = "http://{$host}:{$port}";

        $this->info('🔥 Starting Laravel server...');
        $this->info('🔥 Starting npm dev...');
        $this->newLine();

        $this->info("🌐 App URL : {$url}");
        $this->info("❌ Press 'Q' then ENTER to stop server");
        $this->newLine();

        // Artisan Serve
        $artisan = new \Symfony\Component\Process\Process([
            'php',
            'artisan',
            'serve',
            "--host={$host}",
            "--port={$port}"
        ]);

        $artisan->setTimeout(null);
        $artisan->start();

        // NPM Dev (Windows)
        $npm = new \Symfony\Component\Process\Process([
            'npm.cmd',
            'run',
            'dev'
        ]);

        $npm->setTimeout(null);
        $npm->start();

        // Listen for "q"
        while (true) {
            $input = trim(fgets(STDIN));

            if (strtolower($input) === 'q') {
                $this->warn('🛑 Stopping servers...');

                $artisan->stop();
                $npm->stop();

                $this->info('✅ Server stopped.');
                break;
            }
        }

        return self::SUCCESS;
    }
}
