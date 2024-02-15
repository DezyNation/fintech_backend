<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundRequest;
use App\Http\Resources\GeneralResource;
use App\Models\Fund;
use Illuminate\Http\Request;

class FundRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Fund::where('user_id', $request->user()->id)->paginate(10);
        return new GeneralResource($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FundRequest $request)
    {
        $data = Fund::create([
            'user_id' => $request->user()->id ?? '9b0b0131-d23b-4c45-b7b7-be20fc0b32b6',
            'transaction_id' => $request->transactionId,
            'transaction_date' => $request->transactionDate,
            'amount' => $request->amount,
            'opening_balance' => $request->user()->wallet ?? 0,
            'closing_balance' => $request->user()->wallet ?? 0,
            'user_remarks' => $request->userRemarks
        ]);

        return new GeneralResource($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $data = Fund::where(['user_id' => $request->user()->id, 'id' => $id])->get();
        return new GeneralResource($data);
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
