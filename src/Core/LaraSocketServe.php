<?php

namespace Bagherkeshmiri\LaraSocket\Core;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class LaraSocketServe
{
    private string $host;
    private int $port;
    private array $clients = [];
    private array $handshakes = [];
    private array $rates = [];
    private array $userClients = []; // mapping user_id => client_id

    public function __construct(string $host = '127.0.0.1', int $port = 6001)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function run(): void
    {
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        if (!$server) throw new RuntimeException("Cannot start server: $errstr ($errno)");

        stream_set_blocking($server, false);
        $this->clients[(int)$server] = $server;
        $this->handshakes[(int)$server] = false;

        $this->logInfo("Listening on ws://{$this->host}:{$this->port}");

        while (true) {
            $read = $this->clients;
            $write = $except = null;
            if (stream_select($read, $write, $except, 0, 200000) === false) continue;

            foreach ($read as $res) {
                if ($res === $server) {
                    $conn = stream_socket_accept($server, 0);
                    if ($conn) {
                        stream_set_blocking($conn, false);
                        $id = (int)$conn;
                        $this->clients[$id] = $conn;
                        $this->handshakes[$id] = false;
                    }
                    continue;
                }

                $id = (int)$res;
                $data = @fread($res, 2048);

                if ($data === false || $data === '') {
                    $this->disconnectClient($id);
                    continue;
                }

                // handshake
                if (!$this->handshakes[$id] && preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $data, $m)) {
                    $key = trim($m[1]);
                    $token = $this->extractToken($data);

                    if (!$this->isAuthorized($token)) {
                        $this->logInfo("Unauthorized connection attempt from client {$id}");
                        fclose($res);
                        $this->disconnectClient($id);
                        continue;
                    }

                    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
                    $headers = "HTTP/1.1 101 Switching Protocols\r\n" .
                        "Upgrade: websocket\r\n" .
                        "Connection: Upgrade\r\n" .
                        "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
                    fwrite($res, $headers);
                    $this->handshakes[$id] = true;

                    // ذخیره user_id -> client_id
                    $userId = $this->getUserIdFromToken($token);
                    if ($userId) $this->userClients[$userId] = $id;
                    continue;
                }

                // process message
                if ($this->handshakes[$id]) {
                    $msg = $this->decodeFrame($data);
                    if ($msg === '') continue;

                    if (!$this->allowMessage($id)) {
                        $this->logInfo("Rate limit exceeded for client {$id}");
                        fclose($res);
                        $this->disconnectClient($id);
                        continue;
                    }

                    // فقط broadcast یا پیام به کل
                    $this->broadcast($msg, $exclude = $id);
                }
            }
        }
    }

    private function extractToken(string $headers): ?string
    {
        if (preg_match('#GET\s+([^\s]+)\s+HTTP/1\.1#', $headers, $m)) {
            $url = $m[1];
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $qs);
            return $qs['token'] ?? null;
        }
        return null;
    }

    private function isAuthorized(?string $token): bool
    {
        $mode = config('larasocket.auth_mode', 'none');
        if ($mode === 'none') return true;
        if ($mode === 'sanctum') return $this->validateSanctumToken($token);
        return false;
    }

    private function validateSanctumToken(?string $token): bool
    {
        if (empty($token)) return false;
        if (!class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            Log::warning('Sanctum is not installed but LARASOCKET_AUTH_MODE=sanctum');
            return false;
        }

        try {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$accessToken) return false;
            if (!$accessToken->tokenable) return false;
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) return false;
            return true;
        } catch (Throwable $e) {
            Log::error("LaraSocket Sanctum token error: " . $e->getMessage());
            return false;
        }
    }

    private function getUserIdFromToken(string $token): ?int
    {
        try {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            return $accessToken->tokenable->id ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function allowMessage(int $id): bool
    {
        $cfg = config('larasocket.rate_limit');
        $limit = (int)($cfg['messages'] ?? 10);
        $seconds = (int)($cfg['per_seconds'] ?? 1);
        $now = microtime(true);
        $this->rates[$id] = array_filter($this->rates[$id] ?? [], fn($t) => ($now - $t) <= $seconds);

        if (count($this->rates[$id]) >= $limit) return false;
        $this->rates[$id][] = $now;
        return true;
    }

    private function broadcast(string $msg, ?int $exclude = null): void
    {
        $decoded = json_decode($msg, true);
        foreach ($this->userClients as $userId => $clientId) {
            if (!isset($this->clients[$clientId]) || !$this->handshakes[$clientId]) continue;
            if ($exclude !== null && $clientId === $exclude) continue;

            // فرمت پیام سازگار با Echo/Pusher
            $payload = [
                'event' => $decoded['event'] ?? 'message',
                'data' => $decoded['data'] ?? $decoded,
            ];

            @fwrite($this->clients[$clientId], $this->encodeFrame(json_encode($payload)));
        }
    }

    private function disconnectClient(int $id): void
    {
        if (isset($this->clients[$id])) {
            @fclose($this->clients[$id]);
            unset($this->clients[$id], $this->handshakes[$id], $this->rates[$id]);
            // حذف از mapping user_id
            $this->userClients = array_filter($this->userClients, fn($cid) => $cid !== $id);
        }
    }

    private function encodeFrame(string $text): string
    {
        $b1 = 0x81;
        $len = strlen($text);
        if ($len <= 125) return chr($b1) . chr($len) . $text;
        if ($len <= 65535) return chr($b1) . chr(126) . pack('n', $len) . $text;
        return chr($b1) . chr(127) . pack('J', $len) . $text;
    }

    private function decodeFrame(string $data): string
    {
        if (strlen($data) < 6) return '';
        $len = ord($data[1]) & 127;
        if ($len === 126) {
            $masks = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($len === 127) {
            $masks = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $payload = substr($data, 6);
        }

        $text = '';
        $l = strlen($payload);
        for ($i = 0; $i < $l; ++$i) $text .= $payload[$i] ^ $masks[$i % 4];
        return mb_check_encoding($text, 'UTF-8') ? $text : '';
    }

    private function logInfo(string $msg): void
    {
        if (config('larasocket.log_channel')) {
            Log::channel(config('larasocket.log_channel'))->info($msg);
        } else {
            Log::info($msg);
        }
    }
}
