<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DocumentController extends Controller
{

    public function panVerification(Request $request)
    {
        $request->validate([
            'pan_number' => ['required', 'regex:[A-Z]{5}[0-9]{4}[A-Z]{1}']
        ]);

        $data = [
            'pan_number' => $request->pan_number,
            'purpose' => 1,
            'purpose_desc' => 'onboarding',
            'initiator_id' => config('services.eko.initiator_id')
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post(config('services.eko.base_url') . '/v1/pan/verify', $data);

        $user = User::findOrFail($request->user()->id);
        if ($response['status'] == 0) {
            $user->first_name = $response['data']['first_name'];
            $user->middle_name = $response['data']['middle_name'];
            $user->last_name = $response['data']['last_name'];
            $user->name = $response['data']['pan_returned_name'];
            $user->pan_number = $response['data']['pan_number'];
            $user->save();
        }

        return new GeneralResource($user);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
