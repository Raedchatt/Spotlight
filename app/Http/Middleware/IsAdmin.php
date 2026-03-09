<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to users with the 'admin' role.
 * Returns 403 Forbidden for non-admin authenticated users.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return $next($request);
    }
}
