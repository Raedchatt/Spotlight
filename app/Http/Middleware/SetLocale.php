<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get locale from cookie, then header, then session, fallback to config
        // Note: API routes don't have a session, so we check hasSession() first to avoid a RuntimeException
        $locale = $request->cookie('locale') 
               ?? $request->header('X-Locale') 
               ?? ($request->hasSession() ? $request->session()->get('locale') : null) 
               ?? config('app.locale');

        if (in_array($locale, ['en', 'fr', 'ar'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
