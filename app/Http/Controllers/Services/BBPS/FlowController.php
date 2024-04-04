<?php

namespace App\Http\Controllers\Services\Bbps;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\BbpsTransactionRequest;
use App\Models\Bbps;

class FlowController extends Controller
{
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
    public function store(BbpsTransactionRequest $request)
    {
        $reference_id = uniqid('BBPS-');
        $transaction = $this->initiateRequests($request, $reference_id);

        $bbps = Bbps::create([
            'user_id' => $request->user()->id,
            'operator_id' => $request->operator_id,
            'amount' => $request->amount,
            'status' => $transaction->status,
            'transaction_id' => $transaction->transaction_id,
            'utility_number' => $request->utility_number,
            'phone_number' => $request->phone_number
        ]);

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated", 0, $request->amount, []);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), $request->operator_id, 'bbps', $request->amount);

        $this->releaseLock($request->user()->id);

        return new GeneralResource($bbps);
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
