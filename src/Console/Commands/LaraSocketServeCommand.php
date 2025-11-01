<?php

namespace Bagherkeshmiri\LaraSocket\Console\Commands;

use Bagherkeshmiri\LaraSocket\Core\LaraSocketServe;
use Illuminate\Console\Command;
use Throwable;

class LaraSocketServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You can override host/port using:
     * php artisan larasocket:serve --host=0.0.0.0 --port=9000
     */
    protected $signature = 'larasocket:serve {--host=} {--port=}';
    protected $description = 'Run the LaraSocket WebSocket server';

    public function handle()
    {
        // Get options or fallback to config
        $host = $this->option('host') ?? config('larasocket.host');
        $port = $this->option('port') ?? config('larasocket.client_port');


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
