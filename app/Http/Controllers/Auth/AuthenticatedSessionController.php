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
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    public function checkCredentials(Request $request)
    {
        $request->only(['email', 'password']);

        $identifier = $request['email'];

        $user = User::where('email', $identifier)->orWhere('phone_number', $identifier)->first();

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
        return response()->json($this->respondWithToken(['user' => $user])->cookie("token", $token, auth('api')->factory()->getTTL() * 60, '/', null, false, false));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
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
    public function destroy(Request $request): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
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
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
