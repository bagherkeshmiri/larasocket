# LaraSocket
Secure native PHP WebSocket for Laravel (no external packages)

LaraSocket is a lightweight, secure, and native WebSocket server for Laravel.
Supports token-based authentication, internal admin port, rate limiting, UTF-8 validation, logging, and publishable config.

---

## Features
- Token-based authentication
- Server port (127.0.0.1) for internal broadcasts
- Rate limiting & maximum clients
- UTF-8 validation and logging
- Publishable configuration

---

## Installation

1. Place the package in your Laravel project:
```bash
packages/bagherkeshmiri/larasocket
````

2. Add a path repository to `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/bagherkeshmiri/larasocket"
    }
]
```

3. Install the package via Composer:

```bash
composer require bagherkeshmiri/larasocket:dev-main
```

4. Publish the configuration:

```bash
php artisan vendor:publish --provider="Bagherkeshmiri\LaraSocket\LaraSocketServiceProvider" --tag=config
```

5. Edit `config/larasocket.php` to set:

    * Host and ports
    * Token validator
    * Rate limits, max clients, etc.

6. Start the WebSocket server:

```bash
php artisan ws:serve
```

---

## Browser Usage

```js
const ws = new WebSocket('ws://127.0.0.1:9000/?token=API_TOKEN');

ws.onopen = () => console.log('Connected to LaraSocket!');
ws.onmessage = (msg) => console.log('Message:', msg.data);
```

---

## Laravel Broadcast Usage

```php
$fp = stream_socket_client('tcp://127.0.0.1:9001');
fwrite($fp, 'پیام');
fclose($fp);
```

---

## Notes

* Admin port (`127.0.0.1`) is only for internal messages and broadcasts.
* Ensure the token validator is implemented in your config.
* Supports UTF-8 validation and rate limiting to prevent abuse.

---

## License

MIT
