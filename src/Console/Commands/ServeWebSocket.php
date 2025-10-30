<?php
fclose($adminConn);
}
unset($read[array_search($serverAdmin, $read, true)]);
}


foreach ($read as $res) {
    $id = (int)$res;
    $data = @fread($res, 2048);
    if ($data === false || $data === '') {
        $this->disconnectClient($id);
        continue;
    }


    if (!$this->handshakes[$id] && preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $data, $m)) {
        $key = trim($m[1]);
        $token = $this->extractToken($data);
        if (!$this->validateToken($token)) {
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
        continue;
    }


    if ($this->handshakes[$id]) {
        $msg = $this->decodeFrame($data);
        if ($msg === '') continue;
        if (!$this->allowMessage($id)) {
            fclose($res);
            $this->disconnectClient($id);
            continue;
        }
        $this->broadcast($msg, $exclude = $id);
    }
}
}


fclose($serverClient);
fclose($serverAdmin);
}

private function extractToken(string $headers): ?string
if (!empty($qs['token'])) return $qs['token'];
}
return null;
}


private function validateToken(?string $token): bool
{
    $validator = config('ws.token_validator');
    return is_callable($validator) ? (bool) call_user_func($validator, $token) : false;
}


private function allowMessage(int $id): bool
{
    $cfg = config('ws.rate_limit');
    $limit = (int)$cfg['messages'];
    $seconds = (int)$cfg['per_seconds'];
    $now = microtime(true);
    $this->rates[$id] = array_filter($this->rates[$id] ?? [], fn($t) => ($now - $t) <= $seconds);
    if (count($this->rates[$id]) >= $limit) return false;
    $this->rates[$id][] = $now;
    return true;
}


private function broadcast(string $msg, ?int $exclude = null): void
{
    foreach ($this->clients as $id => $c) {
        if (!$this->handshakes[$id]) continue;
        if ($exclude !== null && $id === $exclude) continue;
        @fwrite($c, $this->encodeFrame($msg));
    }
}


private function disconnectClient(int $id): void
{
    if (isset($this->clients[$id])) {
        @fclose($this->clients[$id]);
        unset($this->clients[$id], $this->handshakes[$id], $this->rates[$id]);
    }
}


private function encodeFrame(string $text): string
{
    $b1 = 0x81;
    $len = strlen($text);
    if ($len <= 125) return chr($b1).chr($len).$text;
    if ($len <= 65535) return chr($b1).chr(126).pack('n',$len).$text;
    return chr($b1).chr(127).pack('J',$len).$text;
}


private function decodeFrame(string $data): string
{
    if (strlen($data)<6) return '';
    $len = ord($data[1]) & 127;
    if ($len===126) {$masks=substr($data,4,4);$payload=substr($data,8);}
    elseif ($len===127) {$masks=substr($data,10,4);$payload=substr($data,14);}
    else {$masks=substr($data,2,4);$payload=substr($data,6);}
    $text='';$l=strlen($payload);
    for($i=0;$i<$l;++$i)$text.=$payload[$i]^$masks[$i%4];
    return mb_check_encoding($text,'UTF-8')?$text:'';
}


private function logInfo(string $msg): void
{
    if (config('ws.log_channel')) Log::channel(config('ws.log_channel'))->info($msg);
    else Log::info($msg);
}
}