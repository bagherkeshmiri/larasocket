<?php


return [
// host to bind (use 0.0.0.0 to listen on all interfaces)
    'host' => env('LARASOCKET_HOST', '127.0.0.1'),
// port for browser WebSocket (ws://host:client_port)
    'client_port' => env('LARASOCKET_CLIENT_PORT', 9000),
// admin port for internal app broadcasts (bind to localhost for safety)
    'admin_port' => env('LARASOCKET_SERVER_PORT', 9001),


// security
    'max_clients' => env('LARASOCKET_MAX_CLIENTS', 200),
    'rate_limit' => [
        'messages' => env('LARASOCKET_RATE_MESSAGES', 20), // messages
        'per_seconds' => env('LARASOCKET_RATE_SECONDS', 10), // per N seconds
    ],


// default token validator
// Should be a callable: function(string $token): bool
// By default it checks users table 'api_token' column.
    'token_validator' => function ($token) {
        if (empty($token)) return false;
        try {
            $model = \App\Models\User::class;
            if (!class_exists($model)) return false;
            return (bool) $model::where('api_token', $token)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    },


// logging channel (Laravel Log facade will be used)
    'log_channel' => env('LARASOCKET_LOG_CHANNEL', null),
];