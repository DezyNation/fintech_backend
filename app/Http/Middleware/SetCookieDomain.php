<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetCookieDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $host = $request->headers->get('origin');
        Log::info('Current host: ' . $host);

        // Define your domain rules
        if (Str::endsWith($host, 'dainypay.in')) {
            config(['session.domain' => '.dainypay.in']);
        } elseif (Str::endsWith($host, 'zeropay.info')) {
            config(['session.domain' => '.zeropay.info']);
        } else {
            config(['session.domain' => '.finoma.in']);
        }

        return $response;
    }
}
