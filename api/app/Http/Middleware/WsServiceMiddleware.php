<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WsServiceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-WS-TOKEN');
        $expected = env('WS_SERVICE_TOKEN');

        // ✅ Segurança simples: só o WS (que conhece o token) pode chamar estes endpoints
        if (!$expected || !$token || !hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
