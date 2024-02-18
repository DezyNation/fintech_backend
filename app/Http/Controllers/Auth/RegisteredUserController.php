<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // 'name' => ['required', 'string', 'max:255'],
            // 'phone_number' => ['required', 'digits:10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 'mpin' => ['required', 'confirmed'],
            // 'terms' => ['accepted']
        ]);

        $role = Role::where('default', true)->first();

        $user = User::create([
            // 'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'mpin' => Hash::make($request->mpin)
        ])->assignRole($role->name);

        event(new Registered($user));

        Auth::login($user);

        return response()->json(['data' => 'registered sucessfully']);
    }

    public function createToken(Request $request)
    {
        $request->validate(['email' => 'required|unique:new_registration_token']);

        DB::table('new_registration_token')->insert([
            'email' => $request->email,
            'token' => Str::uuid(),
            'expiry_at' => Carbon::now()->addDay(),
            'created_at' => now()
        ]);
    }
}
