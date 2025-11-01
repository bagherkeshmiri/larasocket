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
 * - Token-based authentication
 * - Rate limiting and maximum client restrictions
 * - Logging via Laravel Log channels
 *
 * Example .env variables:
 *
 * LARASOCKET_HOST=127.0.0.1
 * LARASOCKET_CLIENT_PORT=9000
 * LARASOCKET_SERVER_PORT=9001
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
    | Token Validator
    |--------------------------------------------------------------------------
    |
    | A callable function that validates a client's authentication token.
    | Each WebSocket client must include a valid token in the query string
    | or the "Sec-WebSocket-Protocol" header.
    |
    | Example client connection:
    |   new WebSocket("ws://127.0.0.1:9000/?token=YOUR_TOKEN");
    |
    | This default implementation checks the 'api_token' column
    | of your User model. You can replace it with any custom logic.
    |
    */
    'token_validator' => function ($token) {
        if (empty($token)) return false;
        try {
            $model = \App\Models\User::class;
            if (!class_exists($model)) return false;

            // Example: check token in 'api_token' field
            return (bool) $model::where('api_token', $token)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    },

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
