<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\GeneralResource;

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
            'name' => ['nullable', 'string', 'max:20'],
            'phone_nmber' => ['nullable', 'digits:10'],
        ]);

        $password = Str::random(8);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
