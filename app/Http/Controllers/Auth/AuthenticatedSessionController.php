<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    public function checkCredentials(Request $request)
    {
        $request->only(['email', 'password']);

        $user = User::whereAny(['email', 'phone_number'], '=', $request->email)->first();

        if (!$user || !Hash::check($request['password'], $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['Credentials do not match our records.']
            ]);
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkCredentials($request);

        $credentials = $request->only(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        $user['roles'] = auth()->user()->getRoleNames()->first();
        $cookie = cookie("token", $token, auth()->factory()->getTTL() * 60, '/', env('COOKIE_DOMAIN'), true, true);
        return response()->json($this->respondWithToken(['user' => $user]))->withCookie($cookie);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(['user' => auth()->user(), 'role' => auth()->user()->getRoleNames()->first()]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        if (Auth::check()) {
            auth()->logout();
        }
        $cookie = cookie("token", null, -1, '/', env('COOKIE_DOMAIN'), true, true);
        return response()->json(['message' => 'log out successfully.'])->withCookie($cookie);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
