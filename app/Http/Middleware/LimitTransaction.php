<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LimitTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $phone = $request->input("phone_number");
        $key = "transaction:" . $phone;

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 4)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json(
                [
                    "message" =>
                        "Too many transactions. Limit is 4 per 24 hours.",
                    "retry_after_seconds" => $seconds,
                ],
                429,
            );
        }

        $response = $next($request);

        // Only count if transaction was successful
        if ($response->isSuccessful()) {
            RateLimiter::hit($key, decaySeconds: 86400); // 24 hours
        }

        return $response;
    }
}
