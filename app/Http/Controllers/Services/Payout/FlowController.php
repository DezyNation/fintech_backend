<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use App\Http\Resources\GeneralResource;
use App\Models\Payout;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GeneralResource::collection(Payout::paginate(30));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayoutRequest $request)
    {
        $lock = Cache::lock($request->user()->id, 5);
        if (!$lock->get()) {
            throw new HttpResponseException(response()->json(['message' => "Failed to acquire lock"], 423));
        }

        if ($request->provider == 'paydeer') {
            $class = new PaydeerController();
            $class->initiateTransaction($request);
        }

        Payout::create([
            'user_id' => $request->user()->id,
            'provider' => $request->provider,
            'reference_id' => uniqid('PYT'),
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'mode' => $request->mode,
            'status' => $request->status,
            'description' => $request->description,
            'metadata' => json_encode([]),
            'remarks' => $request->remarks
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
