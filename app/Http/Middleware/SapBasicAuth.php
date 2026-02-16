<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SapBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = (string) $request->getUser();
        $pass = (string) $request->getPassword();

        $expectedUser = (string) config('services.sap_basic.user');
        $expectedPass = (string) config('services.sap_basic.pass');

        if ($user === '' || $pass === '' || !hash_equals($expectedUser, $user) || !hash_equals($expectedPass, $pass)) {
            return response()->json([
                'status' => 401,
                'response_code' => 'UNAUTHORIZED',
                'message' => 'Unauthorized',
                'data' => null,
            ], 401)->header('WWW-Authenticate', 'Basic realm="SAP Webhook"');
        }

        return $next($request);
    }
}
