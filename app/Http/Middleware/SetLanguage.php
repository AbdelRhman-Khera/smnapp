<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = substr($request->header('Accept-Language'), 0, 2);
        $supportedLocales = ['en', 'ar', 'tr'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'en';
        }
        App::setLocale($locale);
        return $next($request);
    }
}
