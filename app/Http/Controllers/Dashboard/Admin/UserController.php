<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\GeneralResource;
use App\Mail\SendPassword;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new GeneralResource(User::all()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users'],
            'first_name' => ['nullable', 'string', 'max:20'],
            'middle_name' => ['nullable', 'string', 'max:20'],
            'last_name' => ['nullable', 'string', 'max:20'],
            'phone_nmber' => ['nullable', 'digits:10'],
            'email' => ['required', 'email', 'unique:users']
        ]);

        $password = Str::random(8);

        User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . $request->middle_name . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($password)
        ]);

        Mail::to($request->email)
            ->send(new SendPassword($password, 'password'));

        return response()->json(['data' => 'credentials sent.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return new GeneralResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $first_name = $request->first_name ?? $user->first_name;
        $middle_name = $request->middle_name ?? $user->middle_name;
        $last_name = $request->last_name ?? $user->last_name;
        $user->update([
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'name' => $first_name . $middle_name . $last_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email
        ]);

        return new GeneralResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function sendCredential(Request $request, User $user): JsonResource
    {
        $request->validate([
            'channel' => ['required', 'in:email,phone_number'],
            'email' => ['required_if:channel,email', 'exists:users'],
            'phone_number' => ['required_if:channel,phone_number', 'exists:users'],
            'credential_type' => ['required', 'in:password,pin']
        ]);

        if ($request->channel == 'email') {
            return $this->emailCredentials($request, $user);
        } else {
            return $this->smsCredentials($request, $user);
        }
    }

    public function emailCredentials(Request $request, User $user): JsonResource
    {
        $password = Str::random(8);
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        Mail::to($request->email)
            ->send(new SendPassword($password, $request->credential_type));

        return new GeneralResource($user);
    }

    public function smsCredentials(Request $request, User $user): JsonResource
    {
        $password = Str::random(8);
        $user->update([
            $request->credential_type => Hash::make($password)
        ]);

        //  TODO: Implement SMS sending functionality.

        return new GeneralResource($user);
    }
}
