<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function wallet(): JsonResource
    {
        $user = auth()->user();
        return new GeneralResource(['wallet' => $user->wallet]);
    }

    public function updateProfile(Request $request): JsonResource
    {
        $user = User::find($request->user()->id);
        $first_name = $request->first_name ?? $user->first_name;
        $middle_name = $request->middle_name ?? $user->middle_name;
        $last_name = $request->last_name ?? $user->last_name;
        $user->update([
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'name' => $first_name . $middle_name . $last_name
        ]);

        return new GeneralResource($user);
    }
}
