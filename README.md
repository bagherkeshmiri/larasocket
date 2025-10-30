# Laravel Simple WebSocket
Secure native PHP WebSocket for Laravel (no external packages)


- Token-based authentication
- Admin port (127.0.0.1) for internal broadcasts
- Rate limiting & max clients
- UTF-8 validation, logging
- Publishable config


Installation:
1. Put in `packages/laravel-simple-ws`
2. Add path repo to composer.json
3. `composer require vendor/laravel-simple-ws:dev-main`
4. Publish config
5. Edit `config/ws.php` for host, ports, token validator
6. Start server `php artisan ws:serve`


Browser usage:
```js
const ws = new WebSocket('ws://127.0.0.1:9000/?token=API_TOKEN');
```
Laravel broadcast:
```php
$fp = stream_socket_client('tcp://127.0.0.1:9001');
fwrite($fp,'پیام');
fclose($fp);
```