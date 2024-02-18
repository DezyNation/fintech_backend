<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    public function wallet(): JsonResource
    {
        $user = auth()->user();
        return new GeneralResource(['wallet' => $user->wallet]);
    }
}
