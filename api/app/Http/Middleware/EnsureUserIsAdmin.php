<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o user existe e se o tipo é 'A' (Admin)
        if ($request->user() && $request->user()->type == 'A') {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden: Apenas administradores.'], 403);
    }
}