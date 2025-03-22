<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SkipCsrfForPayment
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('payment/callback1')) {
            return $next($request);
        }

        return app(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->handle($request, $next);
    }
}
