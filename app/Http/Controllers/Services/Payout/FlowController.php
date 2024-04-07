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
        
        $lock = $this->lockRecords($request->user()->id);
        if (!$lock->get()) {
            throw new HttpResponseException(response()->json(['data' => ['message' => "Failed to acquire lock"]], 423));
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
        $transaction = $instance->initiateTransaction($request, $reference_id);

        if ($transaction['status'] != 'success') {
            $lock->release();
            abort(400, $transaction['message']);
        }

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
