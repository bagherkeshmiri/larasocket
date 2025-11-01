<?php

/**
 * -----------------------------------------------------------------------------
 * Laravel Native WebSocket (LaraSocket) Configuration
 * -----------------------------------------------------------------------------
 * This file defines the main configuration for your native WebSocket server.
 * It works without any external packages and integrates directly with Laravel.
 *
 * Features:
 * - Separate ports for browser (client) and internal (admin) connections
 * - Optional token-based authentication using Laravel Sanctum
 * - Rate limiting and maximum client restrictions
 * - Logging via Laravel Log channels
 *
 * Example .env variables:
 *
 * LARASOCKET_HOST=127.0.0.1
 * LARASOCKET_CLIENT_PORT=9000
 * LARASOCKET_SERVER_PORT=9001
 * LARASOCKET_AUTH_MODE=sanctum
 * LARASOCKET_MAX_CLIENTS=200
 * LARASOCKET_RATE_MESSAGES=20
 * LARASOCKET_RATE_SECONDS=10
 * LARASOCKET_LOG_CHANNEL=stack
 */

return [

    /*
    |--------------------------------------------------------------------------
    | WebSocket Host
    |--------------------------------------------------------------------------
    |
    | The host or IP address to bind the WebSocket server to.
    | Use "0.0.0.0" to listen on all interfaces (useful for Docker or LAN).
    |
    */
    'host' => env('LARASOCKET_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Client Port
    |--------------------------------------------------------------------------
    |
    | Port number for browser connections.
    | Example: ws://127.0.0.1:9000
    |
    */
    'client_port' => env('LARASOCKET_CLIENT_PORT', 9000),

    /*
    |--------------------------------------------------------------------------
    |  Server Port
    |--------------------------------------------------------------------------
    |
    | Internal port used by your Laravel app to broadcast messages.
    | For security, this port should only be accessible locally (127.0.0.1).
    |
    */
    'server_port' => env('LARASOCKET_SERVER_PORT', 9001),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | - max_clients: maximum number of concurrent WebSocket clients allowed
    | - rate_limit: restricts how many messages a single client can send
    |               within a specific time window to prevent spam/DoS.
    |
    */
    'max_clients' => env('LARASOCKET_MAX_CLIENTS', 200),

    'rate_limit' => [
        'messages' => env('LARASOCKET_RATE_MESSAGES', 20), // number of messages allowed
        'per_seconds' => env('LARASOCKET_RATE_SECONDS', 10), // time window in seconds
    ],


    /*
    |--------------------------------------------------------------------------
    | Authentication Mode
    |--------------------------------------------------------------------------
    |
    | Available options:
    |   - 'none'     : No authentication required.
    |   - 'sanctum'  : Uses Laravel Sanctum token validation.
    |
    | Example:
    |   LARASOCKET_AUTH_MODE=sanctum
    |
    */
    'auth_mode' => env('LARASOCKET_AUTH_MODE', 'none'),

    /*
   |--------------------------------------------------------------------------
   | Custom Token Validator (optional)
   |--------------------------------------------------------------------------
   |
   | Path to a callable that validates WebSocket tokens.
   | Example: 'App\\Support\\LaraSocketValidator::check'
   |
   */
    'custom_token_validator' => env('LARASOCKET_TOKEN_VALIDATOR', 'App\\Support\\LaraSocketValidator::check'),

    /*
    |--------------------------------------------------------------------------
    | Logging Channel
    |--------------------------------------------------------------------------
    |
    | Specifies which Laravel log channel should handle WebSocket logs.
    | Set to null to use the default channel.
    | Example:
    |   LARASOCKET_LOG_CHANNEL=single
    |
    */
    'log_channel' => env('LARASOCKET_LOG_CHANNEL', null),

];
