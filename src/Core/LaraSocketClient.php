<?php

namespace Bagherkeshmiri\LaraSocket\Core;

use Illuminate\Support\Facades\Log;

class LaraSocketClient
{
    private string $host;
    private int $port;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host ?? config('larasocket.host', '127.0.0.1');
        $this->port = $port ?? config('larasocket.server_port', 9001);
    }

    public function sendToUser($userId, array $data): bool
    {
        $msg = json_encode([
            'type' => 'private',
            'user_id' => $userId,
            'payload' => $data,
        ]);

        return $this->sendRaw($msg);
    }

    public function broadcast(array $data): bool
    {
        $msg = json_encode([
            'type' => 'broadcast',
            'payload' => $data,
        ]);

        return $this->sendRaw($msg);
    }

    private function sendRaw(string $msg): bool
    {
        $fp = @stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, 2);

        if (!$fp) {
            Log::error("LaraSocketClient cannot connect to {$this->host}:{$this->port} - $errstr ($errno)");
            return false;
        }

        fwrite($fp, $msg);
        fclose($fp);

        return true;
    }
}
