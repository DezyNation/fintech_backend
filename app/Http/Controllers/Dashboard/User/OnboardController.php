<?php

namespace App\Http\Controllers\Dashboard\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class OnboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function ekoOnboard(Request $request)
    {
        $user = $request->user();
        $data = [
            'initiator_id' => config('services.eko.initiator_id'),
            'pan_number' => $user->pan_number,
            'mobile' => $user->phone_number,
            'first_name' => $user->first_name,
            'email' => $user->email,
            'residence_address' => $user->address->makeHidden(['id', 'user_id', 'created_at', 'updated_at']),
            'dob' => $user->dob,
            'shop_name' => $user->shop_name
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . '/ekoapi/v1/user/onboard', $data);

        if ($response['status'] == 0) {
            $user = User::findOrFail($user->id);
            $user->eko_user_code = $response['data']['user_code'];
            $user->save();
        }

        return $user;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
