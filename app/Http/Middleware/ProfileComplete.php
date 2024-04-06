<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (is_null($user->phone_number) || is_null($user->date_of_birth) || is_null($user->shop_name) || is_null($user->pan_number) || count($user->address) == 0) {
            return response()->json(['message' => 'Please complete your profile.'], 400);
        }
        return $next($request);
    }
}
