<?php

namespace App\Http\Controllers\Services\Payout;

use Carbon\Carbon;
use App\Models\Payout;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\PayoutRequest;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Payout::where('user_id', $request->user()->id)
            ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
            ->paginate(30);

        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayoutRequest $request): JsonResource
    {
        $reference_id = uniqid('PAY-');
        $transaction = $this->initiateRequests($request, $reference_id);

        $payout = Payout::create([
            'user_id' => $request->user()->id,
            'provider' => $request->provider,
            'reference_id' => $reference_id,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'mode' => $request->mode,
            'status' => $transaction['status'],
            'description' => $transaction['message'],
            'remarks' => $request->remarks
        ]);

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated", 0, $request->amount, []);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), $request->amount);

        $this->releaseLock($request->user()->id);

        return new GeneralResource($payout);
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
