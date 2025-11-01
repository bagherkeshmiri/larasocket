<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth:sanctum')->post('/larasocket/auth', function (Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // If user already has a token, use it; otherwise generate one
    if (!$user->api_token) {
        $user->api_token = bin2hex(random_bytes(32));
        $user->save();
    }

    return response()->json([
        'user_id' => $user->id,
        'token' => $user->api_token,
        'expires_in' => 86400, // 24h for example
    ]);
});
