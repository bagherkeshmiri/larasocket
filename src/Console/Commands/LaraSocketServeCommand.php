<?php

namespace Bagherkeshmiri\LaraSocket\Console\Commands;

use Bagherkeshmiri\LaraSocket\Core\LaraSocketServe;
use Illuminate\Console\Command;
use Throwable;

class LaraSocketServeCommand extends Command
{
    protected $signature = 'larasocket:serve {--host=127.0.0.1} {--port=6001}';
    protected $description = 'Run the LaraSocket WebSocket server';

    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');

        $this->info("🚀 Starting LaraSocket WebSocket server on ws://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop the server");

        try {
            $server = new LaraSocketServe($host, $port);
            $server->run();
        } catch (Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
