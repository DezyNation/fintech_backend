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
use App\Models\Service;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class FlowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $reference_id = $request['transaction_id'];
        $utr = $request['utr'];
        $account_number = $request['account_number'];

        if (!empty($reference_id) || !empty($utr) || !empty($account_number)) {
            $data = Payout::where(['user_id' => $request->user()->id])
                ->fiterByRequest($request)
                ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
                ->paginate(30);
        } else {
            $data = Payout::where('user_id', $request->user()->id)
                ->whereBetween('created_at', [$request->from ?? Carbon::now()->startOfDay(), $request->to ?? Carbon::now()->endOfDay()])
                ->paginate(30);
        }

        return GeneralResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayoutRequest $request): JsonResource
    {

        $lock = $this->lockRecords($request->user()->id);
        if (!$lock->get()) {
            abort(423, "Can't lock user account");
        }

        $service = Service::findOrFail($request->service_id);
        $class_name = Str::of($service->provider . "_" . "controller")->studly();
        $class = __NAMESPACE__ . "\\" . $class_name;
        $instance = new $class;
        if (!class_exists($class)) {
            abort(501, "Provider not supported.");
            $lock->release();
        }

        $reference_id = uniqid('PAY-');
        $transaction_request = $instance->initiateTransaction($request, $reference_id);

        if ($transaction_request['data']['status'] != 'success') {
            $lock->release();
            abort(400, $transaction_request['data']['message']);
        }
        
        if (in_array($transaction_request['data']['transaction_status'], ['hold', 'initiated'])) {
            $status = "pending";
        } else {
            $status = $transaction_request['data']['transaction_status'];
        }

        $payout = Payout::create([
            'user_id' => $request->user()->id,
            'provider' => $service->provider,
            'reference_id' => $reference_id,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'beneficiary_name' => $request->beneficiary_name,
            'mode' => $request->mode,
            'amount' => $request->amount,
            'utr' => $transaction_request['data']['utr'],
            'status' => $status,
            'description' => $transaction_request['data']['message'],
            'remarks' => $request->remarks,
            'metadata' => $transaction_request['response']
        ]);

        TransactionController::store($request->user(), $reference_id, 'payout', "Payout initiated", 0, $request->amount);
        $commission_class = new CommissionController;
        $commission_class->distributeCommission($request->user(), $request->amount, $reference_id);

        $this->releaseLock($request->user()->id);

        return new GeneralResource($payout);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        $data = DB::transaction(function () use ($id) {
            $payout = Payout::where(function ($q) {
                $q->where('status', 'initiated')
                    ->orWhere('status', 'hold');
            })->findOrFail($id);

            $class_name = Str::of($payout->provider . "_" . "controller")->studly();
            $class = __NAMESPACE__ . "\\" . $class_name;
            $instance = new $class;
            if (!class_exists($class)) {
                abort(501, "Provider not supported.");
            }

            $transaction_request = $instance->updateTransaction($payout->reference_id);

            if ($transaction_request['data']['status'] != 'success') {
                abort(400, $transaction_request['data']['message']);
            }

            if ($transaction_request['data']['transaction_status'] == ('failed' || 'refunded')) {

                $lock = $this->lockRecords($payout->user_id);
                if (!$lock->get()) {
                    abort(423, "Can't lock user account");
                }

                $payout->status = 'failed';
                $payout->save();
                TransactionController::reverseTransaction($payout->reference_id);
                $lock->release();
            }
            return new GeneralResource($payout);
        }, 2);

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
